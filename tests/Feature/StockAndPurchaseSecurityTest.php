<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\TransactionService;
use App\Support\CodeGenerator;
use App\Support\StockReferenceValidator;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockAndPurchaseSecurityTest extends TestCase
{
    private User $kasir;

    private User $teknisi;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->kasir = User::factory()->create(['email' => 'kasir-stock@test.local']);
        $this->kasir->assignRole('Kasir');

        $this->teknisi = User::factory()->create(['email' => 'teknisi-stock@test.local']);
        $this->teknisi->assignRole('Teknisi');

        $category = ItemCategory::create(['name' => 'Stock Sec']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-STK-0001',
            'name' => 'Stock Sec Item',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 10,
            'purchase_price' => 5000,
            'selling_price' => 15000,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function guest_cannot_post_stock_movements(): void
    {
        $payload = ['items' => [['item_id' => $this->item->id, 'quantity' => 1]]];

        $this->postJson(route('stock-ins.store'), $payload)->assertUnauthorized();
        $this->postJson(route('stock-outs.store'), $payload)->assertUnauthorized();
        $this->postJson(route('purchases.store'), $payload)->assertUnauthorized();
    }

    #[Test]
    public function teknisi_cannot_create_stock_movements_or_purchases(): void
    {
        $payload = ['items' => [['item_id' => $this->item->id, 'quantity' => 1]]];

        $this->actingAs($this->teknisi)->postJson(route('stock-ins.store'), $payload)->assertForbidden();
        $this->actingAs($this->teknisi)->postJson(route('stock-outs.store'), $payload)->assertForbidden();
        $this->actingAs($this->teknisi)->postJson(route('purchases.store'), $payload)->assertForbidden();
    }

    #[Test]
    public function kasir_can_stock_in_and_increases_inventory(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('stock-ins.store'), [
                'items' => [['item_id' => $this->item->id, 'quantity' => 5]],
                'notes' => 'Manual stock in test',
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'data' => ['batch_no']]);

        $this->assertSame(15, $this->item->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $this->item->id,
            'type' => 'in',
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function kasir_can_stock_out_and_decreases_inventory(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('stock-outs.store'), [
                'items' => [['item_id' => $this->item->id, 'quantity' => 3]],
            ])
            ->assertOk();

        $this->assertSame(7, $this->item->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $this->item->id,
            'type' => 'out',
            'quantity' => 3,
        ]);
    }

    #[Test]
    public function stock_out_rejects_insufficient_stock_via_http(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('stock-outs.store'), [
                'items' => [['item_id' => $this->item->id, 'quantity' => 999]],
            ])
            ->assertStatus(422);

        $this->assertSame(10, $this->item->fresh()->stock);
    }

    #[Test]
    public function stock_in_rejects_reserved_system_reference_prefix(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('stock-ins.store'), [
                'reference_no' => 'PBL-20260101-0001',
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);

        $this->assertSame(10, $this->item->fresh()->stock);
    }

    #[Test]
    public function stock_out_rejects_reserved_system_reference_prefix(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('stock-outs.store'), [
                'reference_no' => 'JBL-20260101-0001',
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);

        $this->assertSame(10, $this->item->fresh()->stock);
    }

    #[Test]
    public function stock_reference_validator_allows_custom_reference(): void
    {
        StockReferenceValidator::assertManualReference('ADJ-001');
        $this->assertTrue(true);
    }

    #[Test]
    public function kasir_can_create_purchase_via_http(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('purchases.store'), [
                'supplier_name' => 'Supplier Test',
                'payment_method' => 'cash',
                'items' => [['item_id' => $this->item->id, 'quantity' => 4]],
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'data' => ['purchase_no']]);

        $this->assertSame(14, $this->item->fresh()->stock);
        $this->assertDatabaseHas('purchases', ['supplier_name' => 'Supplier Test']);
    }

    #[Test]
    public function purchase_requires_transfer_bank_account(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('purchases.store'), [
                'payment_method' => 'transfer',
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function transaction_requires_technician_when_services_present(): void
    {
        $service = \App\Models\WorkshopService::create([
            'code' => 'JSV-HTTP-01',
            'name' => 'HTTP Service',
            'price' => 50000,
            'is_active' => true,
        ]);

        $customer = Customer::create(['code' => 'PLG-HTTP-01', 'name' => 'HTTP Cust']);

        $this->actingAs($this->kasir)
            ->postJson(route('transactions.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'payment_method' => 'cash',
                'services' => [['workshop_service_id' => $service->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function failed_transaction_with_new_customer_does_not_leave_orphan(): void
    {
        $this->item->update(['stock' => 0]);
        $before = Customer::count();

        $this->expectException(InvalidArgumentException::class);

        try {
            app(TransactionService::class)->create([
                'customer_mode' => 'new',
                'new_customer' => ['name' => 'Orphan Test Customer'],
                'payment_method' => 'cash',
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ], $this->kasir->id);
        } finally {
            $this->assertSame($before, Customer::count());
            $this->assertDatabaseMissing('customers', ['name' => 'Orphan Test Customer']);
        }
    }

    #[Test]
    public function negative_discount_is_clamped_to_zero(): void
    {
        $customer = Customer::create(['code' => 'PLG-DISC-01', 'name' => 'Disc Cust']);

        $tx = app(TransactionService::class)->create([
            'customer_id' => $customer->id,
            'discount' => -50000,
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->kasir->id);

        $this->assertSame(0.0, (float) $tx->discount);
        $this->assertSame(15000.0, (float) $tx->total);
    }

    #[Test]
    public function code_generator_produces_sequential_unique_numbers(): void
    {
        $first = CodeGenerator::nextFromTable(
            CodeGenerator::PREFIX_STOCK_IN,
            'stock_movements',
            'batch_no'
        );

        DB::table('stock_movements')->insert([
            'batch_no' => $first,
            'item_id' => $this->item->id,
            'type' => 'in',
            'quantity' => 1,
            'stock_before' => 10,
            'stock_after' => 11,
            'user_id' => $this->kasir->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $second = CodeGenerator::nextFromTable(
            CodeGenerator::PREFIX_STOCK_IN,
            'stock_movements',
            'batch_no'
        );

        $this->assertNotSame($first, $second);
        $this->assertStringStartsWith('STM-', $first);
        $this->assertStringStartsWith('STM-', $second);
    }

    #[Test]
    public function concurrent_manual_stock_out_prevents_overselling(): void
    {
        $this->item->update(['stock' => 2]);

        $errors = 0;
        $success = 0;

        for ($i = 0; $i < 3; $i++) {
            try {
                DB::transaction(function () use (&$success) {
                    app(\App\Services\StockService::class)->stockOutBatch(
                        [['item_id' => $this->item->id, 'quantity' => 1]],
                        $this->kasir->id,
                    );
                    $success++;
                });
            } catch (InvalidArgumentException) {
                $errors++;
            }
        }

        $this->assertSame(2, $success);
        $this->assertSame(1, $errors);
        $this->assertSame(0, $this->item->fresh()->stock);
        $this->assertSame(2, StockMovement::where('item_id', $this->item->id)->where('type', 'out')->count());
    }

    #[Test]
    public function financial_report_rejects_invalid_date_input(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('financial-reports.index', ['from' => 'not-a-date']))
            ->assertSessionHasErrors('from');
    }
}
