<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDatedCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\CodeGenerator;

#[Fillable(['code', 'name', 'phone', 'email', 'address', 'notes'])]
class Customer extends Model
{
    use GeneratesDatedCode, HasFactory, SoftDeletes;

    protected static function codePrefix(): string
    {
        return CodeGenerator::PREFIX_CUSTOMER;
    }
}
