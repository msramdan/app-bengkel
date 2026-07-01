<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\GeneratesDatedCode;
use App\Models\Concerns\HasEntityPhoto;
use App\Support\CodeGenerator;

#[Fillable([
    'code', 'name', 'photo', 'category_id', 'unit_id', 'stock', 'stock_opname',
    'purchase_price', 'selling_price', 'description', 'is_active',
])]
class Item extends Model
{
    use GeneratesDatedCode, HasEntityPhoto, HasFactory, SoftDeletes;

    protected $appends = ['photo_url'];

    protected static function codePrefix(): string
    {
        return CodeGenerator::PREFIX_ITEM;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'unit_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_opname > 0 && $this->stock <= $this->stock_opname;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('stock_opname', '>', 0)
            ->whereColumn('stock', '<=', 'stock_opname');
    }

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
            'stock' => 'integer',
            'stock_opname' => 'integer',
        ];
    }
}
