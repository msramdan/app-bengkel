<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Support\CodeGenerator;
use App\Support\PaymentMethodResolver;
use Illuminate\Support\Collection;
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
     *   supplier_mode?: string|null,
     *   supplier_id?: int|null,
     *   new_supplier?: array{name?: string, phone?: string|null, email?: string|null, address?: string|null}|null,
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
            $supplierData = $this->resolveSupplier($payload);
            $payment = $this->paymentResolver->resolve(
                $payload['payment_method'] ?? 'cash',
                isset($payload['bank_account_id']) ? (int) $payload['bank_account_id'] : null,
                PaymentMethodResolver::purchaseMethods(),
            );

            $financials = $this->calculateFinancials($resolvedItems, (float) ($payload['discount'] ?? 0));

            $purchaseNo = CodeGenerator::nextFromTable(
                CodeGenerator::PREFIX_PURCHASE,
                'purchases',
                'purchase_no'
            );

            $purchase = Purchase::create([
                'purchase_no' => $purchaseNo,
                'supplier_id' => $supplierData['supplier_id'],
                'supplier_name' => $supplierData['supplier_name'],
                'user_id' => $userId,
                'subtotal' => $financials['subtotal'],
                'discount' => $financials['discount'],
                'total' => $financials['total'],
                'status' => 'completed',
                'notes' => $payload['notes'] ?? null,
                'payment_method' => $payment['payment_method'],
                'bank_account_id' => $payment['bank_account_id'],
            ]);

            $this->stockInForLines(
                $resolvedItems,
                $userId,
                $purchaseNo,
                'Stok masuk otomatis dari pembelian '.$purchaseNo,
            );

            $this->persistLines($purchase, $resolvedItems);

            return $purchase->load(['user', 'items', 'bankAccount', 'supplier']);
        });
    }

    /**
     * @param  array{
     *   discount?: float,
     *   notes?: string|null,
     *   payment_method?: string,
     *   bank_account_id?: int|null,
     *   items: array<int, array{item_id: int, quantity: int}>
     * }  $payload
     */
    public function update(Purchase $purchase, array $payload, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $payload, $userId) {
            $locked = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isCompleted()) {
                throw new InvalidArgumentException('Pembelian tidak dapat diubah.');
            }

            $locked->load('items');

            $itemLines = $payload['items'] ?? [];
            if (empty($itemLines)) {
                throw new InvalidArgumentException('Pembelian harus memiliki minimal satu barang.');
            }

            $previousItemQty = $locked->items
                ->mapWithKeys(fn (PurchaseItem $line) => [(int) $line->item_id => (int) $line->quantity])
                ->all();

            $resolvedItems = $this->resolveItemLines($itemLines, $previousItemQty);

            $payment = $this->paymentResolver->resolve(
                $payload['payment_method'] ?? $locked->payment_method,
                array_key_exists('bank_account_id', $payload)
                    ? ($payload['bank_account_id'] !== null ? (int) $payload['bank_account_id'] : null)
                    : $locked->bank_account_id,
                PaymentMethodResolver::purchaseMethods(),
            );

            $financials = $this->calculateFinancials(
                $resolvedItems,
                (float) ($payload['discount'] ?? $locked->discount),
            );

            $this->applyStockDeltas(
                $previousItemQty,
                $resolvedItems,
                $userId,
                $locked->purchase_no,
            );

            $locked->update([
                'subtotal' => $financials['subtotal'],
                'discount' => $financials['discount'],
                'total' => $financials['total'],
                'notes' => array_key_exists('notes', $payload) ? $payload['notes'] : $locked->notes,
                'payment_method' => $payment['payment_method'],
                'bank_account_id' => $payment['bank_account_id'],
            ]);

            $locked->items()->delete();
            $this->persistLines($locked, $resolvedItems);

            return $locked->fresh(['user', 'items', 'bankAccount', 'supplier']);
        });
    }

    public function cancel(Purchase $purchase, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $userId) {
            $locked = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isCompleted()) {
                throw new InvalidArgumentException('Pembelian tidak dapat dibatalkan.');
            }

            $locked->load('items');

            if ($locked->items->isNotEmpty()) {
                $stockLines = $locked->items
                    ->map(fn (PurchaseItem $line) => [
                        'item_id' => (int) $line->item_id,
                        'quantity' => (int) $line->quantity,
                    ])
                    ->values()
                    ->all();

                $this->stockService->stockOutBatch(
                    $stockLines,
                    $userId,
                    $locked->purchase_no,
                    'Rollback stok pembatalan pembelian '.$locked->purchase_no,
                );
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            return $locked->fresh(['user', 'items', 'bankAccount', 'supplier', 'cancelledByUser']);
        });
    }

    /**
     * @return array{supplier_id: int|null, supplier_name: string|null}
     */
    private function resolveSupplier(array $payload): array
    {
        $mode = $payload['supplier_mode'] ?? null;

        if ($mode === null && ! empty($payload['supplier_id'])) {
            $mode = 'existing';
        }

        if ($mode === null || $mode === 'none' || $mode === '') {
            return ['supplier_id' => null, 'supplier_name' => null];
        }

        return match ($mode) {
            'existing' => $this->resolveExistingSupplier((int) ($payload['supplier_id'] ?? 0)),
            'new' => $this->createSupplierFromPayload($payload['new_supplier'] ?? []),
            default => throw new InvalidArgumentException('Pilih supplier atau isi data supplier baru.'),
        };
    }

    /**
     * @return array{supplier_id: int, supplier_name: string}
     */
    private function resolveExistingSupplier(int $id): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Supplier wajib dipilih.');
        }

        $supplier = Supplier::query()->find($id);

        if (! $supplier) {
            throw new InvalidArgumentException('Supplier tidak ditemukan.');
        }

        return [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
        ];
    }

    /**
     * @param  array{name?: string, phone?: string|null, email?: string|null, address?: string|null}  $data
     * @return array{supplier_id: int, supplier_name: string}
     */
    private function createSupplierFromPayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Nama supplier baru wajib diisi.');
        }

        $supplier = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => $name,
            'phone' => isset($data['phone']) ? trim((string) $data['phone']) ?: null : null,
            'email' => isset($data['email']) ? trim((string) $data['email']) ?: null : null,
            'address' => isset($data['address']) ? trim((string) $data['address']) ?: null : null,
        ]);

        return [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
        ];
    }

    /**
     * @param  array<int, int>  $previousItemQty
     */
    private function resolveItemLines(array $lines, array $previousItemQty = []): Collection
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

            $previousQty = (int) ($previousItemQty[$itemId] ?? 0);
            if ($qty < $previousQty && (int) $item->stock < ($previousQty - $qty)) {
                throw new InvalidArgumentException(
                    "Stok \"{$item->name}\" tidak mencukupi untuk mengurangi pembelian. Tersedia: {$item->stock}."
                );
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

    /**
     * @param  array<int, int>  $previousItemQty
     */
    private function applyStockDeltas(array $previousItemQty, Collection $resolvedItems, int $userId, string $purchaseNo): void
    {
        $newQtyByItem = $resolvedItems
            ->mapWithKeys(fn (array $line) => [(int) $line['item_id'] => (int) $line['quantity']])
            ->all();

        $allItemIds = array_unique(array_merge(array_keys($previousItemQty), array_keys($newQtyByItem)));
        sort($allItemIds);

        $stockInLines = [];
        $stockOutLines = [];

        foreach ($allItemIds as $itemId) {
            $oldQty = (int) ($previousItemQty[$itemId] ?? 0);
            $newQty = (int) ($newQtyByItem[$itemId] ?? 0);
            $delta = $newQty - $oldQty;

            if ($delta > 0) {
                $stockInLines[] = ['item_id' => $itemId, 'quantity' => $delta];
            } elseif ($delta < 0) {
                $stockOutLines[] = ['item_id' => $itemId, 'quantity' => abs($delta)];
            }
        }

        if (! empty($stockInLines)) {
            $this->stockService->stockInBatch(
                $stockInLines,
                $userId,
                $purchaseNo,
                'Stok masuk koreksi pembelian '.$purchaseNo,
            );
        }

        if (! empty($stockOutLines)) {
            $this->stockService->stockOutBatch(
                $stockOutLines,
                $userId,
                $purchaseNo,
                'Stok keluar koreksi pembelian '.$purchaseNo,
            );
        }
    }

    private function stockInForLines(Collection $resolvedItems, int $userId, string $purchaseNo, string $notes): void
    {
        $stockLines = $resolvedItems->map(fn ($line) => [
            'item_id' => $line['item_id'],
            'quantity' => $line['quantity'],
        ])->all();

        $this->stockService->stockInBatch($stockLines, $userId, $purchaseNo, $notes);
    }

    private function persistLines(Purchase $purchase, Collection $resolvedItems): void
    {
        foreach ($resolvedItems as $line) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                ...$line,
            ]);
        }
    }

    /**
     * @return array{subtotal: float, discount: float, total: float}
     */
    private function calculateFinancials(Collection $resolvedItems, float $discount): array
    {
        $subtotal = (float) $resolvedItems->sum('subtotal');
        $discount = max(0, min($discount, $subtotal));

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2),
        ];
    }
}
