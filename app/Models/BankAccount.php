<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['bank_name', 'account_name', 'account_number', 'is_active'])]
class BankAccount extends Model
{
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function displayLabel(): string
    {
        return "{$this->bank_name} — {$this->account_name} ({$this->account_number})";
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
