<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkshopService;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionSecurityTest extends TestCase
{
    private User $kasir;

    private User $teknisi;

    private Customer $customer;

    private Item $item;

    private WorkshopService $service;

    private Technician $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->kasir = User::factory()->create(['email' => 'kasir@test.local']);
        $this->kasir->assignRole('Kasir');

        $this->teknisi = User::factory()->create(['email' => 'teknisi@test.local']);
        $this->teknisi->assignRole('Teknisi');

        $category = ItemCategory::create(['name' => 'Sec Cat']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-SEC-0001',
            'name' => 'Sec Item',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 5,
            'stock_opname' => 1,
            'purchase_price' => 5000,
            'selling_price' => 30000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'code' => 'PLG-SEC-0001',
            'name' => 'Sec Customer',
        ]);

        $this->technician = Technician::create([
            'code' => 'TKN-SEC-0001',
            'name' => 'Sec Technician',
            'is_active' => true,
        ]);

        $this->service = WorkshopService::create([
            'code' => 'JSV-SEC-0001',
            'name' => 'Sec Service',
            'price' => 50000,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function guest_cannot_access_transactions(): void
    {
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
        $this->get(route('transactions.create'))->assertRedirect(route('login'));
        $this->post(route('transactions.store'))->assertRedirect(route('login'));
    }

    #[Test]
    public function teknisi_role_cannot_create_transactions(): void
    {
        $this->actingAs($this->teknisi)
            ->get(route('transactions.create'))
            ->assertForbidden();

        $this->actingAs($this->teknisi)
            ->postJson(route('transactions.store'), [
                'customer_id' => $this->customer->id,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function kasir_can_create_sale_transaction(): void
    {
        $response = $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_id' => $this->customer->id,
                'payment_method' => 'cash',
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'data' => ['transaction_no']]);

        $this->assertSame(4, $this->item->fresh()->stock);
    }

    #[Test]
    public function store_rejects_empty_transaction(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_id' => $this->customer->id,
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function store_rejects_invalid_customer(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_id' => 99999,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function store_rejects_inactive_technician_for_service(): void
    {
        $inactive = Technician::create([
            'code' => 'TKN-SEC-0002',
            'name' => 'Inactive Tech',
            'is_active' => false,
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_id' => $this->customer->id,
                'payment_method' => 'cash',
                'technician_id' => $inactive->id,
                'services' => [['workshop_service_id' => $this->service->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function workshop_service_master_requires_dedicated_permission(): void
    {
        $this->actingAs($this->teknisi)
            ->get(route('workshop-services.index'))
            ->assertForbidden();

        $this->actingAs($this->kasir)
            ->get(route('workshop-services.index'))
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('workshop-services.index'))
            ->assertOk();
    }

    #[Test]
    public function kasir_can_open_transaction_create_form(): void
    {
        $this->actingAs($this->kasir)
            ->get(route('transactions.create'))
            ->assertOk();
    }
}
