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
use App\Services\FinancialReportService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
{
    private User $superAdmin;

    private User $kasir;

    private User $teknisi;

    private Customer $customer;

    private Item $item;

    private Technician $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('Kasir');

        $this->teknisi = User::factory()->create();
        $this->teknisi->assignRole('Teknisi');

        $category = ItemCategory::create(['name' => 'Mgmt Cat']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-MGT-0001',
            'name' => 'Mgmt Item',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 10,
            'stock_opname' => 1,
            'purchase_price' => 10000,
            'selling_price' => 30000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'code' => 'PLG-MGT-0001',
            'name' => 'Mgmt Customer',
        ]);

        $this->technician = Technician::create([
            'code' => 'TKN-MGT-0001',
            'name' => 'Mgmt Tech',
            'commission_percent' => 20,
            'is_active' => true,
        ]);
    }

    private function createSaleTransaction(int $qty = 2): Transaction
    {
        return app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'amount_paid' => 100000,
            'items' => [['item_id' => $this->item->id, 'quantity' => $qty]],
        ], $this->kasir->id);
    }

    #[Test]
    public function super_admin_can_cancel_transaction_and_restore_stock(): void
    {
        $tx = $this->createSaleTransaction(3);
        $this->assertSame(7, $this->item->fresh()->stock);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('transactions.destroy', $tx))
            ->assertOk()
            ->assertJsonPath('message', 'Transaksi berhasil dibatalkan.');

        $tx->refresh();
        $this->assertSame('cancelled', $tx->status);
        $this->assertNotNull($tx->cancelled_at);
        $this->assertSame($this->superAdmin->id, $tx->cancelled_by);
        $this->assertSame(10, $this->item->fresh()->stock);

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $this->item->id,
            'type' => 'in',
            'reference_no' => $tx->transaction_no,
            'quantity' => 3,
        ]);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());
        $this->assertSame(0, $report['sales']['transaction_count']);
        $this->assertSame(0.0, $report['sales']['revenue']);
    }

    #[Test]
    public function kasir_cannot_cancel_transaction(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->kasir)
            ->deleteJson(route('transactions.destroy', $tx))
            ->assertForbidden();
    }

    #[Test]
    public function super_admin_can_increase_item_qty_on_edit_and_stock_decreases(): void
    {
        $tx = $this->createSaleTransaction(2);
        $this->assertSame(8, $this->item->fresh()->stock);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 200000,
                'discount' => 0,
                'items' => [['item_id' => $this->item->id, 'quantity' => 4, 'unit_price' => 30000]],
            ])
            ->assertOk();

        $tx->refresh();
        $this->assertSame(120000.0, (float) $tx->total);
        $this->assertSame(4, $tx->items->first()->quantity);
        $this->assertSame(6, $this->item->fresh()->stock);
    }

    #[Test]
    public function super_admin_can_decrease_item_qty_on_edit_and_stock_increases(): void
    {
        $tx = app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'amount_paid' => 150000,
            'items' => [['item_id' => $this->item->id, 'quantity' => 4]],
        ], $this->kasir->id);
        $this->assertSame(6, $this->item->fresh()->stock);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 100000,
                'discount' => 0,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1, 'unit_price' => 30000]],
            ])
            ->assertOk();

        $tx->refresh();
        $this->assertSame(30000.0, (float) $tx->total);
        $this->assertSame(9, $this->item->fresh()->stock);
    }

    #[Test]
    public function edit_rejects_insufficient_stock_when_increasing_qty(): void
    {
        $tx = $this->createSaleTransaction(2);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 500000,
                'items' => [['item_id' => $this->item->id, 'quantity' => 11, 'unit_price' => 30000]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'Stok'));
    }

    #[Test]
    public function edit_updates_commission_in_financial_report_for_service_transaction(): void
    {
        $service = WorkshopService::create([
            'code' => 'JSV-MGT-01',
            'name' => 'Mgmt Service',
            'price' => 100000,
            'is_active' => true,
        ]);

        $tx = app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'technician_id' => $this->technician->id,
            'payment_method' => 'cash',
            'amount_paid' => 200000,
            'services' => [['workshop_service_id' => $service->id, 'quantity' => 1]],
        ], $this->kasir->id);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'technician_id' => $this->technician->id,
                'payment_method' => 'cash',
                'amount_paid' => 400000,
                'services' => [['workshop_service_id' => $service->id, 'quantity' => 2, 'unit_price' => 100000]],
            ])
            ->assertOk();

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $this->assertSame(200000.0, $report['sales']['revenue']);
        $this->assertSame(40000.0, $report['sales']['technician_commission']);
    }

    #[Test]
    public function kasir_cannot_edit_transaction(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->kasir)
            ->get(route('transactions.edit', $tx))
            ->assertForbidden();
    }

    #[Test]
    public function cannot_edit_cancelled_transaction(): void
    {
        $tx = $this->createSaleTransaction(1);

        app(TransactionService::class)->cancel($tx, $this->superAdmin->id);

        $this->actingAs($this->superAdmin)
            ->get(route('transactions.edit', $tx))
            ->assertNotFound();
    }

    #[Test]
    public function cancel_creates_stock_in_movement_with_transaction_reference(): void
    {
        $tx = $this->createSaleTransaction(2);

        app(TransactionService::class)->cancel($tx, $this->superAdmin->id);

        $movement = StockMovement::query()
            ->where('item_id', $this->item->id)
            ->where('type', 'in')
            ->where('reference_no', $tx->transaction_no)
            ->latest('id')
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(2, $movement->quantity);
    }

    #[Test]
    public function guest_cannot_edit_or_cancel_transaction(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->get(route('transactions.edit', $tx))->assertRedirect(route('login'));
        $this->putJson(route('transactions.update', $tx), [
            'payment_method' => 'cash',
            'amount_paid' => 100000,
            'items' => [['item_id' => $this->item->id, 'quantity' => 1, 'unit_price' => 30000]],
        ])->assertUnauthorized();
        $this->deleteJson(route('transactions.destroy', $tx))->assertUnauthorized();
    }

    #[Test]
    public function teknisi_cannot_edit_or_cancel_transaction(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->teknisi)
            ->get(route('transactions.edit', $tx))
            ->assertForbidden();

        $this->actingAs($this->teknisi)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 100000,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1, 'unit_price' => 30000]],
            ])
            ->assertForbidden();

        $this->actingAs($this->teknisi)
            ->deleteJson(route('transactions.destroy', $tx))
            ->assertForbidden();
    }

    #[Test]
    public function kasir_cannot_update_transaction_via_api(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->kasir)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 100000,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1, 'unit_price' => 30000]],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function cannot_cancel_transaction_twice_or_double_restore_stock(): void
    {
        $tx = $this->createSaleTransaction(2);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('transactions.destroy', $tx))
            ->assertOk();

        $stockAfterFirstCancel = $this->item->fresh()->stock;

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('transactions.destroy', $tx))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Transaksi tidak dapat dibatalkan.');

        $this->assertSame($stockAfterFirstCancel, $this->item->fresh()->stock);
        $this->assertSame(
            1,
            StockMovement::query()
                ->where('item_id', $this->item->id)
                ->where('type', 'in')
                ->where('reference_no', $tx->transaction_no)
                ->count()
        );
    }

    #[Test]
    public function cannot_update_cancelled_transaction_via_api(): void
    {
        $tx = $this->createSaleTransaction(1);
        app(TransactionService::class)->cancel($tx, $this->superAdmin->id);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 100000,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1, 'unit_price' => 30000]],
            ])
            ->assertNotFound();
    }

    #[Test]
    public function update_rejects_empty_transaction(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 100000,
                'items' => [],
                'services' => [],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Transaksi harus memiliki minimal satu barang atau jasa.');
    }

    #[Test]
    public function update_rejects_insufficient_cash_payment(): void
    {
        $tx = $this->createSaleTransaction(2);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 10000,
                'items' => [['item_id' => $this->item->id, 'quantity' => 2, 'unit_price' => 30000]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Uang diterima kurang dari total bayar.');
    }

    #[Test]
    public function update_rejects_transfer_without_bank_account(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'transfer',
                'items' => [['item_id' => $this->item->id, 'quantity' => 1, 'unit_price' => 30000]],
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function update_rejects_service_without_technician(): void
    {
        $service = WorkshopService::create([
            'code' => 'JSV-MGT-02',
            'name' => 'Mgmt Service 2',
            'price' => 50000,
            'is_active' => true,
        ]);

        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'cash',
                'amount_paid' => 200000,
                'services' => [['workshop_service_id' => $service->id, 'quantity' => 1, 'unit_price' => 50000]],
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function transaction_history_filters_by_period_and_exports_pdf(): void
    {
        $inRange = $this->createSaleTransaction(1);
        $outOfRange = $this->createSaleTransaction(1);
        $outOfRange->forceFill(['created_at' => now()->subMonths(2)])->save();

        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $this->actingAs($this->superAdmin)
            ->get(route('transactions.index', compact('from', 'to')))
            ->assertOk()
            ->assertSee('Filter Periode')
            ->assertSee('Export PDF');

        $rows = $this->actingAs($this->superAdmin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('transactions.index', compact('from', 'to')))
            ->assertOk()
            ->json('data');

        $numbers = collect($rows)->pluck('transaction_no');
        $this->assertTrue($numbers->contains($inRange->transaction_no));
        $this->assertFalse($numbers->contains($outOfRange->transaction_no));

        $this->actingAs($this->superAdmin)
            ->get(route('transactions.export-pdf', compact('from', 'to')))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function completed_transaction_can_print_a4_invoice(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->superAdmin)
            ->get(route('transactions.invoice', ['transaction' => $tx, 'format' => 'a4']))
            ->assertOk()
            ->assertSee('invoice-a4')
            ->assertSee('NOTA PENJUALAN')
            ->assertSee($tx->transaction_no)
            ->assertSee($this->customer->name);
    }

    #[Test]
    public function cancelled_transaction_invoice_returns_not_found(): void
    {
        $tx = $this->createSaleTransaction(1);
        app(TransactionService::class)->cancel($tx, $this->superAdmin->id);

        $this->actingAs($this->superAdmin)
            ->get(route('transactions.invoice', $tx))
            ->assertNotFound();
    }

    #[Test]
    public function update_clears_cash_fields_when_switching_to_qris(): void
    {
        $tx = $this->createSaleTransaction(1);

        $this->actingAs($this->superAdmin)
            ->putJson(route('transactions.update', $tx), [
                'payment_method' => 'qris',
                'items' => [['item_id' => $this->item->id, 'quantity' => 1, 'unit_price' => 30000]],
            ])
            ->assertOk();

        $tx->refresh();
        $this->assertSame('qris', $tx->payment_method);
        $this->assertNull($tx->cash_received);
        $this->assertNull($tx->cash_change);
    }
}
