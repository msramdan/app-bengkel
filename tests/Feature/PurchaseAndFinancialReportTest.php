<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Technician;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkshopService;
use App\Services\FinancialReportService;
use App\Services\PurchaseService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseAndFinancialReportTest extends TestCase
{
    private User $user;

    private Customer $customer;

    private Technician $technician;

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
            'code' => 'BRG-PBL-0001',
            'name' => 'Item Purchase Test',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 5,
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create(['code' => 'PLG-PBL-01', 'name' => 'Cust']);
        $this->technician = Technician::create(['code' => 'TKN-PBL-01', 'name' => 'Tech', 'commission_percent' => 80, 'is_active' => true]);
    }

    #[Test]
    public function sale_transaction_auto_creates_stock_out_history(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $this->assertSame(3, $this->item->fresh()->stock);

        $movement = StockMovement::query()
            ->where('type', 'out')
            ->where('reference_no', $tx->transaction_no)
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(2, $movement->quantity);
        $this->assertStringContainsString($tx->transaction_no, $movement->notes);
    }

    #[Test]
    public function purchase_increases_stock_and_records_expense(): void
    {
        $supplier = Supplier::create(['code' => 'SUP-PBL-01', 'name' => 'Supplier ABC']);

        $purchase = app(PurchaseService::class)->create([
            'supplier_mode' => 'existing',
            'supplier_id' => $supplier->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 10]],
        ], $this->user->id);

        $this->assertSame(15, $this->item->fresh()->stock);
        $this->assertSame(200000.0, (float) $purchase->total);

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'in',
            'item_id' => $this->item->id,
            'reference_no' => $purchase->purchase_no,
        ]);
    }

    #[Test]
    public function financial_report_includes_sales_commission_and_purchases(): void
    {
        $service = WorkshopService::create([
            'code' => 'JSV-FIN-01',
            'name' => 'Servis Test',
            'price' => 100000,
            'is_active' => true,
        ]);

        app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'technician_id' => $this->technician->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            'services' => [['workshop_service_id' => $service->id, 'quantity' => 1]],
        ], $this->user->id);

        app(PurchaseService::class)->create([
            'items' => [['item_id' => $this->item->id, 'quantity' => 5]],
        ], $this->user->id);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $this->assertSame(1, $report['sales']['transaction_count']);
        $this->assertSame(80000.0, $report['sales']['technician_commission']);
        $this->assertSame(1, $report['purchases']['purchase_count']);
        $this->assertGreaterThan(0, $report['purchases']['expense']);
        $this->assertCount(1, $report['commissions']);
    }

    #[Test]
    public function admin_can_view_technician_commission_transaction_list(): void
    {
        $service = WorkshopService::create([
            'code' => 'JSV-FIN-02',
            'name' => 'Ganti Oli',
            'price' => 100000,
            'is_active' => true,
        ]);

        $tx = app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'technician_id' => $this->technician->id,
            'services' => [['workshop_service_id' => $service->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->actingAs($this->user)
            ->getJson(route('financial-reports.technician-commissions', [
                'technician' => $this->technician->id,
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('data.technician_name', 'Tech')
            ->assertJsonPath('data.transaction_count', 1)
            ->assertJsonPath('data.transactions.0.transaction_no', $tx->transaction_no)
            ->assertJsonPath('data.transactions.0.commission', 80000)
            ->assertJsonPath('data.transactions.0.services_label', 'Ganti Oli');
    }

    #[Test]
    public function teknisi_cannot_view_commission_transaction_list(): void
    {
        $teknisi = User::factory()->create();
        $teknisi->assignRole('Teknisi');

        $this->actingAs($teknisi)
            ->getJson(route('financial-reports.technician-commissions', $this->technician))
            ->assertForbidden();
    }

    #[Test]
    public function kasir_can_create_purchase(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $this->actingAs($kasir)
            ->get(route('purchases.create'))
            ->assertOk();
    }

    #[Test]
    public function financial_report_requires_permission(): void
    {
        $teknisi = User::factory()->create();
        $teknisi->assignRole('Teknisi');

        $this->actingAs($teknisi)
            ->get(route('financial-reports.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_export_financial_report_pdf(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('financial-reports.export-pdf', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'laporan-keuangan-',
            (string) $response->headers->get('content-disposition')
        );
    }
}
