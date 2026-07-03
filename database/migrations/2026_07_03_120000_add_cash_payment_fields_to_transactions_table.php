<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('cash_received', 15, 2)->nullable()->after('bank_account_id');
            $table->decimal('cash_change', 15, 2)->nullable()->after('cash_received');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['cash_received', 'cash_change']);
        });
    }
};
