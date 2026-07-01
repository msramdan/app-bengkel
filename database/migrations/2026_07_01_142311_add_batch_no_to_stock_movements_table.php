<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('batch_no', 50)->nullable()->after('type');
            $table->index('batch_no');
        });

        foreach (DB::table('stock_movements')->whereNull('batch_no')->orderBy('id')->get() as $row) {
            DB::table('stock_movements')
                ->where('id', $row->id)
                ->update(['batch_no' => 'legacy-'.$row->id]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['batch_no']);
            $table->dropColumn('batch_no');
        });
    }
};
