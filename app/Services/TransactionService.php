<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Technician;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionServiceLine;
use App\Models\WorkshopService;
use App\Support\CodeGenerator;
use App\Support\PaymentMethodResolver;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function __construct(
        private StockService $stockService,
        private CommissionCalculator $commissionCalculator,
        private PaymentMethodResolver $paymentResolver,
    ) {}

    /**
     * @param  array{
     *   customer_mode?: 'existing'|'umum'|'new',
     *   customer_id?: int|null,
     *   customer_name?: string|null,
     *   new_customer?: array{name: string, phone?: string|null, address?: string|null},
     *   technician_id?: int|null,
     *   discount?: float,
     *   notes?: string|null,
     *   items?: array<int, array{item_id: int, quantity: int, unit_price?: float}>,
     *   services?: array<int, array{workshop_service_id: int, quantity: int, unit_price?: float}>
     * }  $payload
     */
    public function create(array $payload, int $userId): Transaction
    {
        $itemLines = $payload['items'] ?? [];
        $serviceLines = $payload['services'] ?? [];

        if (empty($itemLines) && empty($serviceLines)) {
            throw new InvalidArgumentException('Transaksi harus memiliki minimal satu barang atau jasa.');
        }

        $hasItems = ! empty($itemLines);
        $hasServices = ! empty($serviceLines);

        $type = match (true) {
            $hasItems && $hasServices => 'combined',
            $hasServices => 'service',
            default => 'sale',
        };

        if ($hasServices && empty($payload['technician_id'])) {
            throw new InvalidArgumentException('Teknisi wajib dipilih untuk transaksi yang memiliki jasa servis.');
        }

        return DB::transaction(function () use ($payload, $userId, $itemLines, $serviceLines, $type, $hasServices) {
            $customerData = $this->resolveCustomer($payload);

            $technician = null;
            if (! empty($payload['technician_id'])) {
                $technician = Technician::query()->find($payload['technician_id']);
                if (! $technician || ! $technician->is_active) {
                    throw new InvalidArgumentException('Teknisi tidak valid atau tidak aktif.');
                }
            }

            $resolvedItems = $this->resolveItemLines($itemLines);
            $resolvedServices = $this->resolveServiceLines($serviceLines);

            $subtotalItems = $resolvedItems->sum('subtotal');
            $subtotalServices = $resolvedServices->sum('subtotal');
            $discount = max(0, (float) ($payload['discount'] ?? 0));
            $gross = (float) $subtotalItems + (float) $subtotalServices;

            $financials = $this->commissionCalculator->calculate(
                (float) $subtotalItems,
                (float) $subtotalServices,
                $discount,
                $technician ? (float) $technician->commission_percent : null,
            );

            $payment = $this->paymentResolver->resolve(
                $payload['payment_method'] ?? 'cash',
                isset($payload['bank_account_id']) ? (int) $payload['bank_account_id'] : null,
            );

            $prefix = match ($type) {
                'sale' => CodeGenerator::PREFIX_SALE,
                'service' => CodeGenerator::PREFIX_SERVICE,
                'combined' => CodeGenerator::PREFIX_COMBINED,
            };

            $transactionNo = CodeGenerator::nextFromTable($prefix, 'transactions', 'transaction_no');

            $transaction = Transaction::create([
                'transaction_no' => $transactionNo,
                'type' => $type,
                'customer_id' => $customerData['customer_id'],
                'customer_name' => $customerData['customer_name'],
                'technician_id' => $hasServices ? $payload['technician_id'] : null,
                'user_id' => $userId,
                'subtotal_items' => $subtotalItems,
                'subtotal_services' => $subtotalServices,
                'discount' => min($discount, $gross),
                'total' => $financials['total'],
                'technician_commission' => $financials['technician_commission'],
                'owner_service_share' => $financials['owner_service_share'],
                'owner_items_share' => $financials['owner_items_share'],
                'owner_total_share' => $financials['owner_total_share'],
                'status' => 'completed',
                'notes' => $payload['notes'] ?? null,
                'payment_method' => $payment['payment_method'],
                'bank_account_id' => $payment['bank_account_id'],
            ]);

            if ($resolvedItems->isNotEmpty()) {
                $stockLines = $resolvedItems->map(fn ($line) => [
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                ])->all();

                $this->stockService->stockOutBatch(
                    $stockLines,
                    $userId,
                    $transactionNo,
                    'Stok keluar otomatis dari transaksi '.$transactionNo,
                );
            }

            foreach ($resolvedItems as $line) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    ...$line,
                ]);
            }

            foreach ($resolvedServices as $line) {
                TransactionServiceLine::create([
                    'transaction_id' => $transaction->id,
                    ...$line,
                ]);
            }

            return $transaction->load(['customer', 'technician', 'user', 'items', 'serviceLines', 'bankAccount']);
        });
    }

    private function resolveItemLines(array $lines): \Illuminate\Support\Collection
    {
        $merged = [];

        foreach ($lines as $line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }

            if (! isset($merged[$itemId])) {
                $merged[$itemId] = [
                    'quantity' => 0,
                    'unit_price' => isset($line['unit_price']) ? (float) $line['unit_price'] : null,
                ];
            } elseif (isset($line['unit_price'])) {
                $merged[$itemId]['unit_price'] = (float) $line['unit_price'];
            }

            $merged[$itemId]['quantity'] += $qty;
        }

        if (empty($merged)) {
            return collect();
        }

        $itemIds = array_keys($merged);
        sort($itemIds);

        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $resolved = collect();

        foreach ($itemIds as $itemId) {
            $entry = $merged[$itemId];
            $qty = $entry['quantity'];
            $item = $items->get($itemId);

            if (! $item) {
                throw new InvalidArgumentException('Barang tidak ditemukan.');
            }

            if (! $item->is_active) {
                throw new InvalidArgumentException("Barang \"{$item->name}\" tidak aktif.");
            }

            if ($item->stock < $qty) {
                throw new InvalidArgumentException(
                    "Stok \"{$item->name}\" tidak mencukupi. Tersedia: {$item->stock}, dibutuhkan: {$qty}."
                );
            }

            $unitPrice = $entry['unit_price'] ?? (float) $item->selling_price;

            if ($unitPrice < 0) {
                throw new InvalidArgumentException("Harga barang \"{$item->name}\" tidak valid.");
            }

            $resolved->push([
                'item_id' => $item->id,
                'item_code' => $item->code,
                'item_name' => $item->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => round($unitPrice * $qty, 2),
            ]);
        }

        return $resolved;
    }

    /**
     * @return array{customer_id: int|null, customer_name: string}
     */
    private function resolveCustomer(array $payload): array
    {
        $mode = $payload['customer_mode'] ?? null;

        if ($mode === null && ! empty($payload['customer_id'])) {
            $mode = 'existing';
        }

        return match ($mode) {
            'umum' => [
                'customer_id' => null,
                'customer_name' => (string) config('workshop.walk_in_customer_label', 'Umum'),
            ],
            'new' => $this->createCustomerFromPayload($payload['new_customer'] ?? []),
            'existing' => $this->resolveExistingCustomer((int) ($payload['customer_id'] ?? 0)),
            default => throw new InvalidArgumentException('Pilih pelanggan, gunakan Umum, atau isi data pelanggan baru.'),
        };
    }

    /**
     * @param  array{name?: string, phone?: string|null, address?: string|null}  $data
     * @return array{customer_id: int, customer_name: string}
     */
    private function createCustomerFromPayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Nama pelanggan baru wajib diisi.');
        }

        $customer = Customer::create([
            'code' => Customer::generateCode(),
            'name' => $name,
            'phone' => isset($data['phone']) ? trim((string) $data['phone']) ?: null : null,
            'address' => isset($data['address']) ? trim((string) $data['address']) ?: null : null,
        ]);

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ];
    }

    /**
     * @return array{customer_id: int, customer_name: string}
     */
    private function resolveExistingCustomer(int $customerId): array
    {
        if ($customerId <= 0) {
            throw new InvalidArgumentException('Pilih pelanggan terdaftar.');
        }

        $customer = Customer::query()->find($customerId);

        if (! $customer) {
            throw new InvalidArgumentException('Pelanggan tidak ditemukan.');
        }

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ];
    }

    private function resolveServiceLines(array $lines): \Illuminate\Support\Collection
    {
        $merged = [];

        foreach ($lines as $line) {
            $serviceId = (int) ($line['workshop_service_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($serviceId <= 0 || $qty <= 0) {
                continue;
            }

            if (! isset($merged[$serviceId])) {
                $merged[$serviceId] = [
                    'quantity' => 0,
                    'unit_price' => isset($line['unit_price']) ? (float) $line['unit_price'] : null,
                ];
            } elseif (isset($line['unit_price'])) {
                $merged[$serviceId]['unit_price'] = (float) $line['unit_price'];
            }

            $merged[$serviceId]['quantity'] += $qty;
        }

        if (empty($merged)) {
            return collect();
        }

        $serviceIds = array_keys($merged);
        $services = WorkshopService::query()
            ->whereIn('id', $serviceIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $resolved = collect();

        foreach ($merged as $serviceId => $entry) {
            $qty = $entry['quantity'];
            $service = $services->get($serviceId);

            if (! $service) {
                throw new InvalidArgumentException('Jasa servis tidak ditemukan atau tidak aktif.');
            }

            $unitPrice = $entry['unit_price'] ?? (float) $service->price;

            if ($unitPrice < 0) {
                throw new InvalidArgumentException("Harga jasa \"{$service->name}\" tidak valid.");
            }

            $resolved->push([
                'workshop_service_id' => $service->id,
                'service_code' => $service->code,
                'service_name' => $service->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => round($unitPrice * $qty, 2),
            ]);
        }

        return $resolved;
    }
}
