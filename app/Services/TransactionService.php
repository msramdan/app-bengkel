<?php

namespace App\Services;

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
     *   customer_id: int,
     *   technician_id?: int|null,
     *   discount?: float,
     *   notes?: string|null,
     *   items?: array<int, array{item_id: int, quantity: int}>,
     *   services?: array<int, array{workshop_service_id: int, quantity: int}>
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

        $technician = null;
        if (! empty($payload['technician_id'])) {
            $technician = Technician::query()->find($payload['technician_id']);
            if (! $technician || ! $technician->is_active) {
                throw new InvalidArgumentException('Teknisi tidak valid atau tidak aktif.');
            }
        }

        return DB::transaction(function () use ($payload, $userId, $itemLines, $serviceLines, $type, $hasServices, $technician) {
            $resolvedItems = $this->resolveItemLines($itemLines);
            $resolvedServices = $this->resolveServiceLines($serviceLines);

            $subtotalItems = $resolvedItems->sum('subtotal');
            $subtotalServices = $resolvedServices->sum('subtotal');
            $discount = (float) ($payload['discount'] ?? 0);

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
                'customer_id' => $payload['customer_id'],
                'technician_id' => $hasServices ? $payload['technician_id'] : null,
                'user_id' => $userId,
                'subtotal_items' => $subtotalItems,
                'subtotal_services' => $subtotalServices,
                'discount' => min($discount, $subtotalItems + $subtotalServices),
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
            $merged[$itemId] = ($merged[$itemId] ?? 0) + $qty;
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
            $qty = $merged[$itemId];
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

            $unitPrice = (float) $item->selling_price;

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

    private function resolveServiceLines(array $lines): \Illuminate\Support\Collection
    {
        $merged = [];

        foreach ($lines as $line) {
            $serviceId = (int) ($line['workshop_service_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($serviceId <= 0 || $qty <= 0) {
                continue;
            }
            $merged[$serviceId] = ($merged[$serviceId] ?? 0) + $qty;
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

        foreach ($merged as $serviceId => $qty) {
            $service = $services->get($serviceId);

            if (! $service) {
                throw new InvalidArgumentException('Jasa servis tidak ditemukan atau tidak aktif.');
            }

            $unitPrice = (float) $service->price;

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
