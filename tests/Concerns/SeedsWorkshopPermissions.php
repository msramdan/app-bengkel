<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait SeedsWorkshopPermissions
{
    protected function seedPermissions(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function grantPermissions(string $roleName, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $role->syncPermissions($permissions);
    }
}
