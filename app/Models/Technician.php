<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\GeneratesDatedCode;
use App\Models\Concerns\HasEntityPhoto;
use App\Support\CodeGenerator;

#[Fillable([
    'code', 'name', 'photo', 'phone', 'email', 'specialty',
    'commission_percent', 'is_active', 'user_id', 'notes',
])]
class Technician extends Model
{
    use GeneratesDatedCode, HasEntityPhoto, HasFactory, SoftDeletes;

    protected $appends = ['photo_url'];

    protected static function codePrefix(): string
    {
        return CodeGenerator::PREFIX_TECHNICIAN;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'commission_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
