<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OilChangeReminderLog extends Model
{
    protected $fillable = [
        'customer_id',
        'transaction_id',
        'phone',
        'message',
        'status',
        'hiwa_job_id',
        'due_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
