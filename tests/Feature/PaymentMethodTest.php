<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Services\PurchaseService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    private User $user;

    private Customer $customer;

    private Item $item;

    private BankAccount $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Admin');

        $category = ItemCategory::create(['name' => 'Pay Test']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-PAY-0001',
            'name' => 'Item Payment Test',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 10,
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create(['code' => 'PLG-PAY-01', 'name' => 'Cust Pay']);
        $this->bank = BankAccount::create([
            'bank_name' => 'BRI',
            'account_name' => 'Atha Motor',
            'account_number' => '9876543210',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function transaction_with_cash_payment_saves_without_bank(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertSame('cash', $tx->payment_method);
        $this->assertNull($tx->bank_account_id);
    }

    #[Test]
    public function transaction_with_transfer_requires_bank_account(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'payment_method' => 'transfer',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);
    }

    #[Test]
    public function transaction_with_transfer_saves_bank_account(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'payment_method' => 'transfer',
            'bank_account_id' => $this->bank->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertSame('transfer', $tx->payment_method);
        $this->assertSame($this->bank->id, $tx->bank_account_id);
    }

    #[Test]
    public function purchase_rejects_qris_payment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PurchaseService::class)->create([
            'payment_method' => 'qris',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);
    }

    #[Test]
    public function purchase_with_transfer_saves_bank_account(): void
    {
        $purchase = app(PurchaseService::class)->create([
            'payment_method' => 'transfer',
            'bank_account_id' => $this->bank->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $this->assertSame('transfer', $purchase->payment_method);
        $this->assertSame($this->bank->id, $purchase->bank_account_id);
    }

    #[Test]
    public function financial_report_breaks_down_payment_sources(): void
    {
        app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        app(TransactionService::class)->create([
            'customer_id' => $this->customer->id,
            'payment_method' => 'transfer',
            'bank_account_id' => $this->bank->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        app(PurchaseService::class)->create([
            'payment_method' => 'transfer',
            'bank_account_id' => $this->bank->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $this->assertSame(15000.0, $report['payment_sources']['inflows']['cash']);
        $this->assertSame(30000.0, $report['payment_sources']['inflows']['transfer_total']);
        $this->assertSame(10000.0, $report['payment_sources']['outflows']['transfer_total']);
        $this->assertCount(1, $report['payment_sources']['inflows']['transfer']);
        $this->assertCount(1, $report['payment_sources']['outflows']['transfer']);
    }

    #[Test]
    public function owner_can_access_bank_accounts_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('bank-accounts.index'))
            ->assertOk();
    }
}
