<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no', 50)->unique();
            $table->enum('type', ['sale', 'service', 'combined']);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 255)->nullable();
            $table->foreignId('technician_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('subtotal_items', 15, 2)->default(0);
            $table->decimal('subtotal_services', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('technician_commission', 15, 2)->default(0);
            $table->decimal('owner_service_share', 15, 2)->default(0);
            $table->decimal('owner_items_share', 15, 2)->default(0);
            $table->decimal('owner_total_share', 15, 2)->default(0);
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->enum('payment_method', ['cash', 'qris', 'transfer'])->default('cash');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cash_received', 15, 2)->nullable();
            $table->decimal('cash_change', 15, 2)->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
            $table->index('payment_method');
            $table->index(['customer_id', 'created_at']);
            $table->index(['technician_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
