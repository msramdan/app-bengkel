<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierPurchaseTest extends TestCase
{
    private User $user;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Admin');

        $category = ItemCategory::create(['name' => 'Test']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-SUP-0001',
            'name' => 'Item Supplier Test',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 5,
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function purchase_with_existing_supplier_links_supplier_id(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-PBL-01',
            'name' => 'Toko Oli Jaya',
        ]);

        $purchase = app(PurchaseService::class)->create([
            'supplier_mode' => 'existing',
            'supplier_id' => $supplier->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $this->assertSame($supplier->id, $purchase->supplier_id);
        $this->assertSame('Toko Oli Jaya', $purchase->supplier_name);
    }

    #[Test]
    public function purchase_with_new_supplier_creates_master_record(): void
    {
        $before = Supplier::count();

        $purchase = app(PurchaseService::class)->create([
            'supplier_mode' => 'new',
            'new_supplier' => [
                'name' => 'Supplier Baru Auto',
                'phone' => '08111',
            ],
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertSame($before + 1, Supplier::count());
        $this->assertNotNull($purchase->supplier_id);
        $this->assertSame('Supplier Baru Auto', $purchase->supplier_name);
        $this->assertDatabaseHas('suppliers', ['name' => 'Supplier Baru Auto', 'phone' => '08111']);
    }

    #[Test]
    public function purchase_without_supplier_is_allowed(): void
    {
        $purchase = app(PurchaseService::class)->create([
            'supplier_mode' => 'none',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertNull($purchase->supplier_id);
        $this->assertNull($purchase->supplier_name);
    }

    #[Test]
    public function kasir_can_create_purchase_with_new_supplier_via_http(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $this->actingAs($kasir)
            ->postJson(route('purchases.store'), [
                'supplier_mode' => 'new',
                'new_supplier' => ['name' => 'HTTP Supplier Baru'],
                'payment_method' => 'cash',
                'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('suppliers', ['name' => 'HTTP Supplier Baru']);
        $this->assertDatabaseHas('purchases', ['supplier_name' => 'HTTP Supplier Baru']);
    }
}
