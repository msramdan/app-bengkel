<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    private User $superAdmin;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->kasir = User::factory()->create(['email' => 'kasir-supplier@test.local']);
        $this->kasir->assignRole('Kasir');
    }

    #[Test]
    public function super_admin_can_create_supplier(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('suppliers.store'), [
                'name' => 'PT Sumber Sparepart',
                'phone' => '08123456789',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'PT Sumber Sparepart');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'PT Sumber Sparepart',
            'phone' => '08123456789',
        ]);
    }

    #[Test]
    public function kasir_can_view_and_create_supplier(): void
    {
        $this->actingAs($this->kasir)
            ->get(route('suppliers.index'))
            ->assertOk();

        $this->actingAs($this->kasir)
            ->postJson(route('suppliers.store'), ['name' => 'Supplier Kasir'])
            ->assertOk();
    }

    #[Test]
    public function teknisi_cannot_manage_suppliers(): void
    {
        $teknisi = User::factory()->create();
        $teknisi->assignRole('Teknisi');

        $this->actingAs($teknisi)
            ->get(route('suppliers.index'))
            ->assertForbidden();
    }

    #[Test]
    public function super_admin_can_update_and_delete_supplier(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-TEST-01',
            'name' => 'Supplier Lama',
        ]);

        $this->actingAs($this->superAdmin)
            ->putJson(route('suppliers.update', $supplier), [
                'name' => 'Supplier Baru',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Supplier Baru');

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('suppliers.destroy', $supplier))
            ->assertOk();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }
}
