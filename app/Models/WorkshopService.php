<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDatedCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'description', 'price', 'is_active'])]
class WorkshopService extends Model
{
    use GeneratesDatedCode, HasFactory;

    protected $table = 'workshop_services';

    protected static function codePrefix(): string
    {
        return 'JSV';
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
