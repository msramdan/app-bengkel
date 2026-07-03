<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');
    }

    #[Test]
    public function super_admin_can_update_role_with_new_config_permissions(): void
    {
        Permission::query()->whereIn('name', ['transaction edit', 'transaction delete'])->delete();

        $role = Role::findByName('Kasir');

        $permissions = collect(config('permissions'))
            ->pluck('access')
            ->flatten()
            ->filter(fn (string $name) => str_starts_with($name, 'transaction'))
            ->values()
            ->all();

        $this->actingAs($this->superAdmin)
            ->put(route('roles.update', $role), [
                'name' => 'Kasir',
                'permissions' => $permissions,
            ])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('permissions', ['name' => 'transaction edit']);
        $this->assertDatabaseHas('permissions', ['name' => 'transaction delete']);
        $this->assertTrue($role->fresh()->hasPermissionTo('transaction edit'));
    }

    #[Test]
    public function super_admin_can_update_super_admin_role(): void
    {
        $role = Role::findByName('Super Admin');

        $this->actingAs($this->superAdmin)
            ->put(route('roles.update', $role), [
                'name' => 'Super Admin',
                'permissions' => ['dashboard view', 'role edit'],
            ])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');
    }
}
