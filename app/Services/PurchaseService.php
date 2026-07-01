<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\CodeGenerator;
use App\Support\PaymentMethodResolver;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseService
{
    public function __construct(
        private StockService $stockService,
        private PaymentMethodResolver $paymentResolver,
    ) {}

    /**
     * @param  array{
     *   supplier_name?: string|null,
     *   discount?: float,
     *   notes?: string|null,
     *   items: array<int, array{item_id: int, quantity: int}>
     * }  $payload
     */
    public function create(array $payload, int $userId): Purchase
    {
        $itemLines = $payload['items'] ?? [];

        if (empty($itemLines)) {
            throw new InvalidArgumentException('Pembelian harus memiliki minimal satu barang.');
        }

        return DB::transaction(function () use ($payload, $userId, $itemLines) {
            $resolvedItems = $this->resolveItemLines($itemLines);

            $subtotal = (float) $resolvedItems->sum('subtotal');
            $discount = max(0, min((float) ($payload['discount'] ?? 0), $subtotal));
            $total = round($subtotal - $discount, 2);

            $payment = $this->paymentResolver->resolve(
                $payload['payment_method'] ?? 'cash',
                isset($payload['bank_account_id']) ? (int) $payload['bank_account_id'] : null,
                PaymentMethodResolver::purchaseMethods(),
            );

            $purchaseNo = CodeGenerator::nextFromTable(
                CodeGenerator::PREFIX_PURCHASE,
                'purchases',
                'purchase_no'
            );

            $purchase = Purchase::create([
                'purchase_no' => $purchaseNo,
                'supplier_name' => $payload['supplier_name'] ?? null,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => 'completed',
                'notes' => $payload['notes'] ?? null,
                'payment_method' => $payment['payment_method'],
                'bank_account_id' => $payment['bank_account_id'],
            ]);

            $stockLines = $resolvedItems->map(fn ($line) => [
                'item_id' => $line['item_id'],
                'quantity' => $line['quantity'],
            ])->all();

            $this->stockService->stockInBatch(
                $stockLines,
                $userId,
                $purchaseNo,
                'Stok masuk otomatis dari pembelian '.$purchaseNo,
            );

            foreach ($resolvedItems as $line) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    ...$line,
                ]);
            }

            return $purchase->load(['user', 'items', 'bankAccount']);
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
            throw new InvalidArgumentException('Daftar barang tidak valid.');
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

            $unitPrice = (float) $item->purchase_price;

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
}
