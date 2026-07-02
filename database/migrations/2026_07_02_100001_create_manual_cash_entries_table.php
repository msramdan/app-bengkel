<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_cash_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no', 32)->unique();
            $table->enum('type', ['income', 'expense']);
            $table->foreignId('category_id')->constrained('financial_categories')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->dateTime('occurred_at');
            $table->enum('payment_method', ['cash', 'qris', 'transfer']);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status', 'occurred_at']);
            $table->index(['category_id', 'occurred_at']);
            $table->index(['payment_method', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_cash_entries');
    }
};
