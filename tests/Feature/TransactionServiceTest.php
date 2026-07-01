<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use App\Models\Technician;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkshopService;
use App\Services\StockService;
use App\Services\TransactionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    private User $user;

    private Customer $customer;

    private Technician $technician;

    private Item $item;

    private WorkshopService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Admin');

        $category = ItemCategory::create(['name' => 'Test Cat']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-TEST-0001',
            'name' => 'Test Item',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 10,
            'stock_opname' => 2,
            'purchase_price' => 10000,
            'selling_price' => 25000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'code' => 'PLG-TEST-0001',
            'name' => 'Test Customer',
        ]);

        $this->technician = Technician::create([
            'code' => 'TKN-TEST-0001',
            'name' => 'Test Technician',
            'commission_percent' => 15,
            'is_active' => true,
        ]);

        $this->service = WorkshopService::create([
            'code' => 'JSV-TEST-0001',
            'name' => 'Test Service',
            'price' => 100000,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function sale_transaction_deducts_stock_and_records_owner_share(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 3]],
        ], $this->user->id);

        $this->assertSame('sale', $tx->type);
        $this->assertSame(75000.0, (float) $tx->subtotal_items);
        $this->assertSame(0.0, (float) $tx->technician_commission);
        $this->assertSame(75000.0, (float) $tx->owner_items_share);
        $this->assertSame(7, $this->item->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $this->item->id,
            'type' => 'out',
            'reference_no' => $tx->transaction_no,
        ]);
    }

    #[Test]
    public function service_transaction_requires_technician_and_calculates_commission_from_technician_percent(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'technician_id' => $this->technician->id,
            'services' => [['workshop_service_id' => $this->service->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertSame('service', $tx->type);
        $this->assertSame(15000.0, (float) $tx->technician_commission);
        $this->assertSame(85000.0, (float) $tx->owner_service_share);
        $this->assertSame(10, $this->item->fresh()->stock);
    }

    #[Test]
    public function combined_transaction_uses_correct_prefix_and_commission(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'technician_id' => $this->technician->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
            'services' => [['workshop_service_id' => $this->service->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertSame('combined', $tx->type);
        $this->assertStringStartsWith('TRX-', $tx->transaction_no);
        $this->assertSame(15000.0, (float) $tx->technician_commission);
        $this->assertSame(50000.0, (float) $tx->subtotal_items);
        $this->assertSame(8, $this->item->fresh()->stock);
        $this->assertCount(1, $tx->items);
        $this->assertCount(1, $tx->serviceLines);
    }

    #[Test]
    public function service_without_technician_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'services' => [['workshop_service_id' => $this->service->id, 'quantity' => 1]],
        ], $this->user->id);
    }

    #[Test]
    public function insufficient_stock_is_rejected_and_rolls_back(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            app(TransactionService::class)->create([
                'customer_id' => $this->customer->id,
                'items' => [['item_id' => $this->item->id, 'quantity' => 999]],
            ], $this->user->id);
        } finally {
            $this->assertSame(10, $this->item->fresh()->stock);
            $this->assertSame(0, Transaction::count());
        }
    }

    #[Test]
    public function transaction_uses_submitted_unit_price_not_master_price(): void
    {
        $service = WorkshopService::create([
            'code' => 'JSV-PRICE-01',
            'name' => 'Servis Price Test',
            'price' => 100000,
            'is_active' => true,
        ]);

        $tx = app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'items' => [[
                'item_id' => $this->item->id,
                'quantity' => 1,
                'unit_price' => 20000,
            ]],
            'services' => [[
                'workshop_service_id' => $service->id,
                'quantity' => 1,
                'unit_price' => 85000,
            ]],
            'technician_id' => $this->technician->id,
        ], $this->user->id);

        $itemLine = $tx->items->first();
        $serviceLine = $tx->serviceLines->first();

        $this->assertSame(20000.0, (float) $itemLine->unit_price);
        $this->assertSame(20000.0, (float) $itemLine->subtotal);
        $this->assertSame(85000.0, (float) $serviceLine->unit_price);
        $this->assertSame(85000.0, (float) $serviceLine->subtotal);
        $this->assertSame(25000.0, (float) $this->item->fresh()->selling_price);
        $this->assertSame(100000.0, (float) $service->fresh()->price);
    }

    #[Test]
    public function concurrent_stock_out_prevents_overselling(): void
    {
        $this->item->update(['stock' => 2]);

        $errors = 0;
        $success = 0;

        for ($i = 0; $i < 3; $i++) {
            try {
                DB::transaction(function () use (&$success) {
                    app(TransactionService::class)->create([
                        'customer_id' => $this->customer->id,
                        'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
                    ], $this->user->id);
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
    public function walk_in_transaction_uses_umum_label_without_customer_record(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_mode' => 'umum',
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertNull($tx->customer_id);
        $this->assertSame('Umum', $tx->customer_name);
        $this->assertSame('Umum', $tx->displayCustomerName());
    }

    #[Test]
    public function new_customer_payload_creates_master_customer(): void
    {
        $before = Customer::count();

        $tx = app(TransactionService::class)->create([
            'customer_mode' => 'new',
            'new_customer' => ['name' => 'Pelanggan Cepat', 'phone' => '08123456789'],
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertSame($before + 1, Customer::count());
        $this->assertDatabaseHas('customers', ['name' => 'Pelanggan Cepat', 'phone' => '08123456789']);
        $this->assertNotNull($tx->customer_id);
        $this->assertSame('Pelanggan Cepat', $tx->customer_name);
    }
}
