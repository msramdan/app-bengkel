<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan snapshot harga beli per baris transaksi, lalu hitung ulang
 * owner_items_share sebagai margin (jual − beli) tanpa mengubah data penjualan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->default(0)->after('unit_price');
        });

        // Backfill dari harga beli master barang saat ini (estimasi terbaik untuk data lama).
        if (Schema::hasTable('items')) {
            DB::table('transaction_items')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $cost = DB::table('items')->where('id', $row->item_id)->value('purchase_price') ?? 0;
                        DB::table('transaction_items')->where('id', $row->id)->update([
                            'unit_cost' => $cost,
                        ]);
                    }
                });
        }

        Transaction::query()
            ->with('items')
            ->orderBy('id')
            ->each(function (Transaction $transaction) {
                $itemsCost = (float) $transaction->items->sum(
                    fn ($line) => (float) $line->unit_cost * (int) $line->quantity
                );

                $ownerItemsShare = round((float) $transaction->subtotal_items - $itemsCost, 2);
                $ownerTotalShare = round((float) $transaction->owner_service_share + $ownerItemsShare, 2);

                $transaction->forceFill([
                    'owner_items_share' => $ownerItemsShare,
                    'owner_total_share' => $ownerTotalShare,
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
