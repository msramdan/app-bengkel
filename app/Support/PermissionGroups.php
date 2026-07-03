<?php

namespace App\Support;

class PermissionGroups
{
    /**
     * @return list<array{group: string, access: list<string>}>
     */
    public static function all(): array
    {
        static $groups = null;

        if ($groups === null) {
            $groups = require config_path('permissions.php');
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return collect(self::all())
            ->pluck('access')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }
}
