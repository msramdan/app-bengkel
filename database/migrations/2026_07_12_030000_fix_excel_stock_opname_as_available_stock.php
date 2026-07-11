<?php

use App\Services\FixMislabelledExcelStockService;
use Illuminate\Database\Migrations\Migration;

/**
 * Koreksi data Excel: nilai di kolom "Stock Opname" sebenarnya stok tersedia.
 * Aman untuk production: tidak mengubah stock barang yang sudah punya stok.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(FixMislabelledExcelStockService::class)->handle();
    }

    public function down(): void
    {
        // Data koreksi satu arah — tidak di-rollback otomatis.
    }
};
