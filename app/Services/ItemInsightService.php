<?php

namespace App\Services;

use App\Models\Item;
use App\Models\TransactionItem;
use Illuminate\Support\Collection;

class ItemInsightService
{
    /**
     * @return array{
     *   stock_value: float,
     *   sku_count: int,
     *   sku_in_stock: int,
     *   unit_count: int
     * }
     */
    public function workshopStock(): array
    {
        $row = Item::query()
            ->selectRaw('COUNT(*) as sku_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END), 0) as sku_in_stock')
            ->selectRaw('COALESCE(SUM(stock), 0) as unit_count')
            ->selectRaw('COALESCE(SUM(stock * purchase_price), 0) as stock_value')
            ->first();

        return [
            'stock_value' => (float) ($row->stock_value ?? 0),
            'sku_count' => (int) ($row->sku_count ?? 0),
            'sku_in_stock' => (int) ($row->sku_in_stock ?? 0),
            'unit_count' => (int) ($row->unit_count ?? 0),
        ];
    }

    /**
     * @return Collection<int, object{
     *   item_id: int,
     *   item_name: string,
     *   item_code: string|null,
     *   photo_url: string|null,
     *   qty_sold: int,
     *   revenue: float,
     *   stock: int
     * }>
     */
    public function bestSellers(int $limit = 6): Collection
    {
        $sold = TransactionItem::query()
            ->select('transaction_items.item_id')
            ->selectRaw('SUM(transaction_items.quantity) as qty_sold')
            ->selectRaw('SUM(transaction_items.subtotal) as revenue')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'completed')
            ->groupBy('transaction_items.item_id')
            ->orderByDesc('qty_sold')
            ->limit($limit)
            ->get();

        if ($sold->isEmpty()) {
            return collect();
        }

        $items = Item::withTrashed()
            ->whereIn('id', $sold->pluck('item_id'))
            ->get()
            ->keyBy('id');

        return $sold->values()->map(function ($row) use ($items) {
            $item = $items->get($row->item_id);

            return (object) [
                'item_id' => (int) $row->item_id,
                'item_name' => $item?->name ?? 'Barang terhapus',
                'item_code' => $item?->code,
                'photo_url' => $item?->photo_url,
                'qty_sold' => (int) $row->qty_sold,
                'revenue' => (float) $row->revenue,
                'stock' => (int) ($item?->stock ?? 0),
            ];
        });
    }
}
