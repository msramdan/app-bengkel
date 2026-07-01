<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard view',
            'user view', 'user create', 'user edit', 'user delete',
            'role view', 'role create', 'role edit', 'role delete',
            'customer view', 'customer create', 'customer edit', 'customer delete',
            'technician view', 'technician create', 'technician edit', 'technician delete',
            'item category view', 'item category create', 'item category edit', 'item category delete',
            'item unit view', 'item unit create', 'item unit edit', 'item unit delete',
            'item view', 'item create', 'item edit', 'item delete',
            'item import', 'item export',
            'stock in view', 'stock in create',
            'stock out view', 'stock out create',
            'stock report view',
            'workshop service view', 'workshop service create', 'workshop service edit', 'workshop service delete',
            'transaction view', 'transaction create',
            'purchase view', 'purchase create',
            'financial report view',
            'bank account view', 'bank account create', 'bank account edit', 'bank account delete',
            'settings view', 'settings edit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(Permission::all());

        $kasir = Role::firstOrCreate(['name' => 'Kasir']);
        $kasir->syncPermissions([
            'dashboard view',
            'customer view', 'customer create', 'customer edit',
            'technician view',
            'item view', 'item category view', 'item unit view',
            'item export',
            'stock in view', 'stock in create',
            'stock out view', 'stock out create',
            'stock report view',
            'transaction view', 'transaction create',
            'purchase view', 'purchase create',
            'financial report view',
        ]);

        $teknisi = Role::firstOrCreate(['name' => 'Teknisi']);
        $teknisi->syncPermissions([
            'dashboard view',
            'customer view',
            'technician view',
            'item view',
            'stock report view',
            'transaction view',
        ]);

        Role::where('name', 'Admin')->each(function (Role $role) {
            $kasir = Role::firstOrCreate(['name' => 'Kasir']);
            foreach ($role->users as $user) {
                $user->syncRoles([$kasir]);
            }
            $role->delete();
        });

        $user = User::updateOrCreate(
            ['email' => 'saepulramdan244@gmail.com'],
            [
                'name' => 'Saepul Ramdan',
                'password' => Hash::make('password'),
            ]
        );

        if (! $user->hasRole('Super Admin')) {
            $user->syncRoles(['Super Admin']);
        }
    }
}
