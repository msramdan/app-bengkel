<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'abbreviation'])]
class ItemUnit extends Model
{
    use HasFactory;

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'unit_id');
    }
}
