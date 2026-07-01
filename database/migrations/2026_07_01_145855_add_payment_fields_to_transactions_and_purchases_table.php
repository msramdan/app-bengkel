<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'qris', 'transfer'])->default('cash')->after('notes');
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained()->nullOnDelete();
            $table->index('payment_method');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'qris', 'transfer'])->default('cash')->after('notes');
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained()->nullOnDelete();
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('payment_method');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('payment_method');
        });
    }
};
