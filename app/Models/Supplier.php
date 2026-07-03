<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDatedCode;
use App\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'phone', 'email', 'address', 'notes'])]
class Supplier extends Model
{
    use GeneratesDatedCode, HasFactory, SoftDeletes;

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    protected static function codePrefix(): string
    {
        return CodeGenerator::PREFIX_SUPPLIER;
    }
}
