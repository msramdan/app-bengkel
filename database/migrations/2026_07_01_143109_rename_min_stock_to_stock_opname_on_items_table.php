<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('min_stock', 'stock_opname');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('stock_opname', 'min_stock');
        });
    }
};
