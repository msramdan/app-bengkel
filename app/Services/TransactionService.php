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
use Illuminate\Support\Collection;
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
        return DB::transaction(function () use ($payload, $userId) {
            $prepared = $this->preparePayload($payload, true);
            $payment = $this->paymentResolver->resolve(
                $payload['payment_method'] ?? 'cash',
                isset($payload['bank_account_id']) ? (int) $payload['bank_account_id'] : null,
            );

            $transactionNo = $this->nextTransactionNo($prepared['type']);
            $cashPayment = $this->resolveCashPayment($payload, (float) $prepared['financials']['total'], $payment['payment_method']);

            $transaction = Transaction::create([
                ...$this->transactionAttributes($prepared, $userId, $transactionNo),
                'status' => 'completed',
                'payment_method' => $payment['payment_method'],
                'bank_account_id' => $payment['bank_account_id'],
                'cash_received' => $cashPayment['cash_received'],
                'cash_change' => $cashPayment['cash_change'],
            ]);

            $this->deductStockForLines($prepared['resolved_items'], $userId, $transactionNo);
            $this->persistLines($transaction, $prepared['resolved_items'], $prepared['resolved_services']);

            return $transaction->load(['customer', 'technician', 'user', 'items', 'serviceLines', 'bankAccount']);
        });
    }

    /**
     * @param  array{
     *   technician_id?: int|null,
     *   discount?: float,
     *   notes?: string|null,
     *   payment_method?: string,
     *   bank_account_id?: int|null,
     *   amount_paid?: float|null,
     *   items?: array<int, array{item_id: int, quantity: int, unit_price?: float}>,
     *   services?: array<int, array{workshop_service_id: int, quantity: int, unit_price?: float}>
     * }  $payload
     */
    public function update(Transaction $transaction, array $payload, int $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $payload, $userId) {
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isCompleted()) {
                throw new InvalidArgumentException('Transaksi tidak dapat diubah.');
            }

            $locked->load(['items', 'serviceLines']);

            $previousItemQty = $locked->items
                ->mapWithKeys(fn (TransactionItem $line) => [(int) $line->item_id => (int) $line->quantity])
                ->all();

            $editPayload = [
                'customer_mode' => $locked->customer_id ? 'existing' : 'umum',
                'customer_id' => $locked->customer_id,
                'technician_id' => $payload['technician_id'] ?? $locked->technician_id,
                'discount' => $payload['discount'] ?? $locked->discount,
                'notes' => array_key_exists('notes', $payload) ? $payload['notes'] : $locked->notes,
                'items' => $payload['items'] ?? [],
                'services' => $payload['services'] ?? [],
            ];

            $prepared = $this->preparePayload($editPayload, true, $previousItemQty);

            $payment = $this->paymentResolver->resolve(
                $payload['payment_method'] ?? $locked->payment_method,
                array_key_exists('bank_account_id', $payload)
                    ? ($payload['bank_account_id'] !== null ? (int) $payload['bank_account_id'] : null)
                    : $locked->bank_account_id,
            );

            $cashPayment = $this->resolveCashPayment(
                $payload,
                (float) $prepared['financials']['total'],
                $payment['payment_method'],
            );

            $this->applyStockDeltas(
                $previousItemQty,
                $prepared['resolved_items'],
                $userId,
                $locked->transaction_no,
            );

            $locked->update([
                ...$this->transactionAttributes($prepared, $userId, $locked->transaction_no, $locked),
                'type' => $prepared['type'],
                'payment_method' => $payment['payment_method'],
                'bank_account_id' => $payment['bank_account_id'],
                'cash_received' => $cashPayment['cash_received'],
                'cash_change' => $cashPayment['cash_change'],
            ]);

            $locked->items()->delete();
            $locked->serviceLines()->delete();
            $this->persistLines($locked, $prepared['resolved_items'], $prepared['resolved_services']);

            return $locked->fresh(['customer', 'technician', 'user', 'items', 'serviceLines', 'bankAccount']);
        });
    }

    public function cancel(Transaction $transaction, int $userId): Transaction
    {
        return DB::transaction(function () use ($transaction, $userId) {
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isCompleted()) {
                throw new InvalidArgumentException('Transaksi tidak dapat dibatalkan.');
            }

            $locked->load('items');

            if ($locked->items->isNotEmpty()) {
                $stockLines = $locked->items
                    ->map(fn (TransactionItem $line) => [
                        'item_id' => (int) $line->item_id,
                        'quantity' => (int) $line->quantity,
                    ])
                    ->values()
                    ->all();

                $this->stockService->stockInBatch(
                    $stockLines,
                    $userId,
                    $locked->transaction_no,
                    'Rollback stok pembatalan transaksi '.$locked->transaction_no,
                );
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            return $locked->fresh(['customer', 'technician', 'user', 'items', 'serviceLines', 'bankAccount', 'cancelledByUser']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   type: string,
     *   customer_data: array{customer_id: int|null, customer_name: string},
     *   technician_id: int|null,
     *   resolved_items: Collection,
     *   resolved_services: Collection,
     *   subtotal_items: float,
     *   subtotal_services: float,
     *   discount: float,
     *   financials: array<string, float>
     * }
     */
    private function preparePayload(array $payload, bool $requireTechnicianForServices, array $previousItemQty = []): array
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

        if ($requireTechnicianForServices && $hasServices && empty($payload['technician_id'])) {
            throw new InvalidArgumentException('Teknisi wajib dipilih untuk transaksi yang memiliki jasa servis.');
        }

        $customerData = $this->resolveCustomer($payload);
        $useMemberPricing = $this->customerQualifiesForMemberPricing($customerData, $payload);

        $technician = null;
        if (! empty($payload['technician_id'])) {
            $technician = Technician::query()->find($payload['technician_id']);
            if (! $technician || ! $technician->is_active) {
                throw new InvalidArgumentException('Teknisi tidak valid atau tidak aktif.');
            }
        }

        $resolvedItems = $this->resolveItemLines($itemLines, $useMemberPricing, $previousItemQty);
        $resolvedServices = $this->resolveServiceLines($serviceLines);

        $subtotalItems = (float) $resolvedItems->sum('subtotal');
        $subtotalServices = (float) $resolvedServices->sum('subtotal');
        $discount = max(0, (float) ($payload['discount'] ?? 0));
        $gross = $subtotalItems + $subtotalServices;

        $financials = $this->commissionCalculator->calculate(
            $subtotalItems,
            $subtotalServices,
            $discount,
            $technician ? (float) $technician->commission_percent : null,
        );

        return [
            'type' => $type,
            'customer_data' => $customerData,
            'technician_id' => $hasServices ? ($payload['technician_id'] ?? null) : null,
            'resolved_items' => $resolvedItems,
            'resolved_services' => $resolvedServices,
            'subtotal_items' => $subtotalItems,
            'subtotal_services' => $subtotalServices,
            'discount' => min($discount, $gross),
            'financials' => $financials,
            'notes' => $payload['notes'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $prepared
     * @return array<string, mixed>
     */
    private function transactionAttributes(array $prepared, int $userId, string $transactionNo, ?Transaction $existing = null): array
    {
        return [
            'transaction_no' => $transactionNo,
            'type' => $prepared['type'],
            'customer_id' => $prepared['customer_data']['customer_id'],
            'customer_name' => $prepared['customer_data']['customer_name'],
            'technician_id' => $prepared['technician_id'] ?? $existing?->technician_id,
            'user_id' => $existing?->user_id ?? $userId,
            'subtotal_items' => $prepared['subtotal_items'],
            'subtotal_services' => $prepared['subtotal_services'],
            'discount' => $prepared['discount'],
            'total' => $prepared['financials']['total'],
            'technician_commission' => $prepared['financials']['technician_commission'],
            'owner_service_share' => $prepared['financials']['owner_service_share'],
            'owner_items_share' => $prepared['financials']['owner_items_share'],
            'owner_total_share' => $prepared['financials']['owner_total_share'],
            'notes' => $prepared['notes'] ?? $existing?->notes,
        ];
    }

    private function nextTransactionNo(string $type): string
    {
        $prefix = match ($type) {
            'sale' => CodeGenerator::PREFIX_SALE,
            'service' => CodeGenerator::PREFIX_SERVICE,
            'combined' => CodeGenerator::PREFIX_COMBINED,
        };

        return CodeGenerator::nextFromTable($prefix, 'transactions', 'transaction_no');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{cash_received: float|null, cash_change: float|null}
     */
    private function resolveCashPayment(array $payload, float $total, string $paymentMethod): array
    {
        if ($paymentMethod !== 'cash') {
            return ['cash_received' => null, 'cash_change' => null];
        }

        $received = isset($payload['amount_paid'])
            ? round((float) $payload['amount_paid'], 2)
            : round($total, 2);

        if ($received < $total) {
            throw new InvalidArgumentException('Uang diterima kurang dari total bayar.');
        }

        return [
            'cash_received' => $received,
            'cash_change' => round($received - $total, 2),
        ];
    }

    private function deductStockForLines(Collection $resolvedItems, int $userId, string $transactionNo): void
    {
        if ($resolvedItems->isEmpty()) {
            return;
        }

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

    private function persistLines(Transaction $transaction, Collection $resolvedItems, Collection $resolvedServices): void
    {
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
    }

    /**
     * @param  array<int, int>  $previousItemQty
     */
    private function applyStockDeltas(array $previousItemQty, Collection $resolvedItems, int $userId, string $transactionNo): void
    {
        $newQtyByItem = $resolvedItems
            ->mapWithKeys(fn (array $line) => [(int) $line['item_id'] => (int) $line['quantity']])
            ->all();

        $allItemIds = array_unique(array_merge(array_keys($previousItemQty), array_keys($newQtyByItem)));
        sort($allItemIds);

        $stockOutLines = [];
        $stockInLines = [];

        foreach ($allItemIds as $itemId) {
            $oldQty = (int) ($previousItemQty[$itemId] ?? 0);
            $newQty = (int) ($newQtyByItem[$itemId] ?? 0);
            $delta = $newQty - $oldQty;

            if ($delta > 0) {
                $stockOutLines[] = ['item_id' => $itemId, 'quantity' => $delta];
            } elseif ($delta < 0) {
                $stockInLines[] = ['item_id' => $itemId, 'quantity' => abs($delta)];
            }
        }

        if (! empty($stockOutLines)) {
            $this->stockService->stockOutBatch(
                $stockOutLines,
                $userId,
                $transactionNo,
                'Stok keluar koreksi transaksi '.$transactionNo,
            );
        }

        if (! empty($stockInLines)) {
            $this->stockService->stockInBatch(
                $stockInLines,
                $userId,
                $transactionNo,
                'Stok kembali koreksi transaksi '.$transactionNo,
            );
        }
    }

    private function resolveItemLines(array $lines, bool $useMemberPricing = false, array $previousItemQty = []): Collection
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

        $query = Item::query()
            ->whereIn('id', $itemIds)
            ->orderBy('id');

        $items = $query->lockForUpdate()->get()
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

            if ($item->stock + (int) ($previousItemQty[$itemId] ?? 0) < $qty) {
                $available = (int) $item->stock + (int) ($previousItemQty[$itemId] ?? 0);
                throw new InvalidArgumentException(
                    "Stok \"{$item->name}\" tidak mencukupi. Tersedia: {$available}, dibutuhkan: {$qty}."
                );
            }

            $unitPrice = $entry['unit_price'] ?? $item->resolveSalePrice($useMemberPricing);

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
            'is_member' => false,
            'phone' => isset($data['phone']) ? trim((string) $data['phone']) ?: null : null,
            'address' => isset($data['address']) ? trim((string) $data['address']) ?: null : null,
        ]);

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ];
    }

    private function customerQualifiesForMemberPricing(array $customerData, array $payload): bool
    {
        $mode = $payload['customer_mode'] ?? null;

        if ($mode === 'umum' || $mode === 'new') {
            return false;
        }

        if (empty($customerData['customer_id'])) {
            return false;
        }

        return (bool) Customer::query()
            ->where('id', $customerData['customer_id'])
            ->value('is_member');
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

    private function resolveServiceLines(array $lines): Collection
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
