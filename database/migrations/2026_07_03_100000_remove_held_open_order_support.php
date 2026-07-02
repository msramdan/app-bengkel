<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')
            ->where('status', 'held')
            ->update([
                'status' => 'cancelled',
                'held_at' => null,
            ]);

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn('held_at');
        });

        DB::statement("UPDATE transactions SET payment_method = 'cash' WHERE payment_method IS NULL");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN payment_method ENUM('cash', 'qris', 'transfer') NOT NULL DEFAULT 'cash'");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('completed', 'cancelled') NOT NULL DEFAULT 'completed'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('held', 'completed', 'cancelled') NOT NULL DEFAULT 'completed'");
        DB::statement("ALTER TABLE transactions MODIFY COLUMN payment_method ENUM('cash', 'qris', 'transfer') NULL DEFAULT NULL");

        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('held_at')->nullable()->after('status');
            $table->index(['status', 'created_at']);
        });
    }
};
