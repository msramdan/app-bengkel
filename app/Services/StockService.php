<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockMovement;
use App\Support\CodeGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    /**
     * @param  array<int, array{item_id: int, quantity: int}>  $lines
     * @return array{batch_no: string, movements: Collection<int, StockMovement>}
     */
    public function stockInBatch(
        array $lines,
        int $userId,
        ?string $referenceNo = null,
        ?string $notes = null,
    ): array {
        return $this->processBatch('in', $lines, $userId, $referenceNo, $notes);
    }

    /**
     * @param  array<int, array{item_id: int, quantity: int}>  $lines
     * @return array{batch_no: string, movements: Collection<int, StockMovement>}
     */
    public function stockOutBatch(
        array $lines,
        int $userId,
        ?string $referenceNo = null,
        ?string $notes = null,
    ): array {
        return $this->processBatch('out', $lines, $userId, $referenceNo, $notes);
    }

    public function stockIn(
        int $itemId,
        int $quantity,
        int $userId,
        ?string $referenceNo = null,
        ?string $notes = null,
    ): StockMovement {
        return $this->stockInBatch(
            [['item_id' => $itemId, 'quantity' => $quantity]],
            $userId,
            $referenceNo,
            $notes,
        )['movements']->first();
    }

    public function stockOut(
        int $itemId,
        int $quantity,
        int $userId,
        ?string $referenceNo = null,
        ?string $notes = null,
    ): StockMovement {
        return $this->stockOutBatch(
            [['item_id' => $itemId, 'quantity' => $quantity]],
            $userId,
            $referenceNo,
            $notes,
        )['movements']->first();
    }

    /**
     * @param  array<int, array{item_id: int, quantity: int}>  $lines
     * @return array{batch_no: string, movements: Collection<int, StockMovement>}
     */
    private function processBatch(
        string $type,
        array $lines,
        int $userId,
        ?string $referenceNo,
        ?string $notes,
    ): array {
        $merged = $this->mergeLines($lines);

        if ($merged->isEmpty()) {
            throw new InvalidArgumentException('Keranjang masih kosong.');
        }

        $itemIds = $merged->keys()->sort()->values()->all();

        return DB::transaction(function () use ($type, $merged, $itemIds, $userId, $referenceNo, $notes) {
            $batchNo = $this->generateBatchNo($type);

            $items = Item::query()
                ->whereIn('id', $itemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== count($itemIds)) {
                throw new InvalidArgumentException('Salah satu barang tidak ditemukan.');
            }

            $movements = collect();

            foreach ($itemIds as $itemId) {
                $quantity = (int) $merged->get($itemId);
                $item = $items->get($itemId);

                if (! $item->is_active) {
                    throw new InvalidArgumentException("Barang \"{$item->name}\" tidak aktif.");
                }

                $stockBefore = (int) $item->stock;
                $stockAfter = $type === 'in'
                    ? $stockBefore + $quantity
                    : $stockBefore - $quantity;

                if ($type === 'out' && $stockAfter < 0) {
                    throw new InvalidArgumentException(
                        "Stok \"{$item->name}\" tidak mencukupi. Tersedia: {$stockBefore}."
                    );
                }

                $item->update(['stock' => $stockAfter]);

                $movements->push(StockMovement::create([
                    'item_id' => $item->id,
                    'type' => $type,
                    'batch_no' => $batchNo,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_no' => $referenceNo,
                    'notes' => $notes,
                    'user_id' => $userId,
                ]));
            }

            return [
                'batch_no' => $batchNo,
                'movements' => $movements,
            ];
        });
    }

    /**
     * @param  array<int, array{item_id: int, quantity: int}>  $lines
     * @return Collection<int, int>
     */
    private function mergeLines(array $lines): Collection
    {
        $merged = collect();

        foreach ($lines as $line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($itemId <= 0) {
                continue;
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Jumlah stok harus lebih dari 0.');
            }

            $merged->put($itemId, (int) $merged->get($itemId, 0) + $quantity);
        }

        return $merged;
    }

    private function generateBatchNo(string $type): string
    {
        $prefix = $type === 'in'
            ? CodeGenerator::PREFIX_STOCK_IN
            : CodeGenerator::PREFIX_STOCK_OUT;

        return CodeGenerator::nextFromTable($prefix, 'stock_movements', 'batch_no');
    }
}
