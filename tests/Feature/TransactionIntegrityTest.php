<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\StockMovement;
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

class TransactionIntegrityTest extends TestCase
{
    private User $user;

    private Customer $customer;

    private Item $item;

    private Technician $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Admin');

        $category = ItemCategory::create(['name' => 'Integrity Cat']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-INT-0001',
            'name' => 'Integrity Item',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 5,
            'stock_opname' => 1,
            'purchase_price' => 10000,
            'selling_price' => 30000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'code' => 'PLG-INT-0001',
            'name' => 'Integrity Customer',
        ]);

        $this->technician = Technician::create([
            'code' => 'TKN-INT-0001',
            'name' => 'Integrity Tech',
            'commission_percent' => 20,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function cancelled_transactions_are_excluded_from_financial_report(): void
    {
        app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $cancelled = app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        Transaction::whereKey($cancelled->id)->update(['status' => 'cancelled']);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $this->assertSame(1, $report['sales']['transaction_count']);
        $this->assertSame(30000.0, $report['sales']['revenue']);
    }

    #[Test]
    public function cash_flow_estimate_matches_revenue_minus_commission_minus_purchases(): void
    {
        $service = WorkshopService::create([
            'code' => 'JSV-INT-01',
            'name' => 'Integrity Service',
            'price' => 100000,
            'is_active' => true,
        ]);

        app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'technician_id' => $this->technician->id,
            'payment_method' => 'cash',
            'discount' => 10000,
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            'services' => [['workshop_service_id' => $service->id, 'quantity' => 1]],
        ], $this->user->id);

        app(PurchaseService::class)->create([
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $expectedCashFlow = round(
            $report['sales']['revenue'] - $report['purchases']['expense'] - $report['sales']['technician_commission'],
            2,
        );

        $this->assertSame(120000.0, $report['sales']['revenue']);
        $this->assertSame(20000.0, $report['sales']['technician_commission']);
        $this->assertSame($expectedCashFlow, $report['profit']['cash_flow_estimate']);
        $this->assertSame($expectedCashFlow, $report['profit']['owner_net_estimate']);
    }

    #[Test]
    public function payment_inflow_total_matches_completed_sales_revenue(): void
    {
        $bank = BankAccount::create([
            'bank_name' => 'BCA',
            'account_name' => 'Atha Motor',
            'account_number' => '1234567890',
            'is_active' => true,
        ]);

        app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'transfer',
            'bank_account_id' => $bank->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $this->assertSame(
            $report['sales']['revenue'],
            $report['payment_sources']['inflows']['total'],
        );
    }

    #[Test]
    public function merged_duplicate_item_lines_deduct_stock_once(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [
                ['item_id' => $this->item->id, 'quantity' => 2],
                ['item_id' => $this->item->id, 'quantity' => 1],
            ],
        ], $this->user->id);

        $this->assertSame(2, $this->item->fresh()->stock);
        $this->assertSame(1, $tx->items()->count());
        $this->assertSame(3, $tx->items->first()->quantity);
        $this->assertSame(
            3,
            (int) StockMovement::where('item_id', $this->item->id)->where('type', 'out')->sum('quantity'),
        );
    }

    #[Test]
    public function stock_movement_matches_transaction_reference_and_quantity(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $movement = StockMovement::query()
            ->where('item_id', $this->item->id)
            ->where('reference_no', $tx->transaction_no)
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(2, $movement->quantity);
        $this->assertSame(5, $movement->stock_before);
        $this->assertSame(3, $movement->stock_after);
        $this->assertSame(3, $this->item->fresh()->stock);
    }
}
