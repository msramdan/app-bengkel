<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description'])]
class ItemCategory extends Model
{
    use HasFactory;

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }
}
