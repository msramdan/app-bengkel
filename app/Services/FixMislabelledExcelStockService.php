<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixMislabelledExcelStockService
{
    /**
     * Koreksi: nilai stock_opname dari Excel sebenarnya stok tersedia.
     * - stock=0 + stock_opname>0 → pindah ke stock (stock in), stock_opname=0
     * - barang yang sudah punya stock → stock tidak diubah, stock_opname=0
     *
     * @return array{moved: int, cleared: int}
     */
    public function handle(): array
    {
        $userId = (int) (User::query()->value('id') ?? 0);
        $stockService = app(StockService::class);
        $moved = 0;
        $cleared = 0;

        DB::transaction(function () use ($userId, $stockService, &$moved, &$cleared) {
            Item::query()
                ->where('stock', 0)
                ->where('stock_opname', '>', 0)
                ->orderBy('id')
                ->each(function (Item $item) use ($userId, $stockService, &$moved) {
                    $qty = (int) $item->stock_opname;

                    $item->update(['stock_opname' => 0]);

                    if ($qty <= 0) {
                        return;
                    }

                    if ($userId > 0) {
                        $stockService->stockIn(
                            $item->id,
                            $qty,
                            $userId,
                            null,
                            'Koreksi: stok tersedia dari kolom Excel yang salah label stock opname'
                        );
                    } else {
                        $item->update(['stock' => $qty]);
                    }

                    $moved++;
                });

            $cleared = Item::query()
                ->where('stock_opname', '!=', 0)
                ->update(['stock_opname' => 0]);
        });

        return compact('moved', 'cleared');
    }
}
