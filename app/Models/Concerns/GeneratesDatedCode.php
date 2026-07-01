<?php

namespace App\Models\Concerns;

use App\Support\CodeGenerator;

trait GeneratesDatedCode
{
    abstract protected static function codePrefix(): string;

    public static function generateCode(): string
    {
        return CodeGenerator::next(static::codePrefix(), static::class);
    }
}
