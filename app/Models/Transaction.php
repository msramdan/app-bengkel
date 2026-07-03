<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'transaction_no', 'type', 'customer_id', 'customer_name', 'technician_id', 'user_id',
    'subtotal_items', 'subtotal_services', 'discount', 'total',
    'technician_commission', 'owner_service_share', 'owner_items_share', 'owner_total_share',
    'status', 'notes', 'payment_method', 'bank_account_id', 'cash_received', 'cash_change',
    'cancelled_at', 'cancelled_by',
])]
class Transaction extends Model
{
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function displayCustomerName(): string
    {
        return $this->customer?->name ?? $this->customer_name ?? '-';
    }

    public function displayPaymentLabel(): string
    {
        return \App\Support\PaymentMethodResolver::label($this->payment_method);
    }
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function serviceLines(): HasMany
    {
        return $this->hasMany(TransactionServiceLine::class);
    }

    protected function casts(): array
    {
        return [
            'subtotal_items' => 'decimal:2',
            'subtotal_services' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'technician_commission' => 'decimal:2',
            'owner_service_share' => 'decimal:2',
            'owner_items_share' => 'decimal:2',
            'owner_total_share' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'cash_change' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }
}
