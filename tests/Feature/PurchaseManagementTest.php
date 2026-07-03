<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseManagementTest extends TestCase
{
    private User $superAdmin;

    private User $kasir;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('Kasir');

        $category = ItemCategory::create(['name' => 'Mgmt Cat']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-PBL-MGT-01',
            'name' => 'Purchase Mgmt Item',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 10,
            'stock_opname' => 1,
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'is_active' => true,
        ]);
    }

    private function createPurchase(int $qty = 5): Purchase
    {
        return app(PurchaseService::class)->create([
            'supplier_mode' => 'none',
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => $qty]],
        ], $this->superAdmin->id);
    }

    #[Test]
    public function super_admin_can_cancel_purchase_and_restore_stock(): void
    {
        $purchase = $this->createPurchase(4);
        $this->assertSame(14, $this->item->fresh()->stock);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('purchases.destroy', $purchase))
            ->assertOk();

        $this->assertSame(10, $this->item->fresh()->stock);
        $this->assertTrue($purchase->fresh()->isCancelled());

        $movement = StockMovement::query()
            ->where('type', 'out')
            ->where('reference_no', $purchase->purchase_no)
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame(4, $movement->quantity);
    }

    #[Test]
    public function cancelled_purchase_is_excluded_from_financial_report(): void
    {
        $purchase = $this->createPurchase(2);

        $reportBefore = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());
        $this->assertSame(20000.0, $reportBefore['purchases']['expense']);

        app(PurchaseService::class)->cancel($purchase, $this->superAdmin->id);

        $reportAfter = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());
        $this->assertSame(0.0, $reportAfter['purchases']['expense']);
        $this->assertSame(0, $reportAfter['purchases']['purchase_count']);
    }

    #[Test]
    public function super_admin_can_increase_purchase_qty_and_stock_increases(): void
    {
        $purchase = $this->createPurchase(2);
        $this->assertSame(12, $this->item->fresh()->stock);

        app(PurchaseService::class)->update($purchase, [
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 5]],
        ], $this->superAdmin->id);

        $this->assertSame(15, $this->item->fresh()->stock);
        $this->assertSame(50000.0, (float) $purchase->fresh()->total);
    }

    #[Test]
    public function super_admin_can_decrease_purchase_qty_and_stock_decreases(): void
    {
        $purchase = $this->createPurchase(5);
        $this->assertSame(15, $this->item->fresh()->stock);

        app(PurchaseService::class)->update($purchase, [
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->superAdmin->id);

        $this->assertSame(12, $this->item->fresh()->stock);
        $this->assertSame(20000.0, (float) $purchase->fresh()->total);
    }

    #[Test]
    public function edit_rejects_insufficient_stock_when_decreasing_qty(): void
    {
        $purchase = $this->createPurchase(5);
        $this->item->update(['stock' => 2]);

        $this->expectException(\InvalidArgumentException::class);

        app(PurchaseService::class)->update($purchase, [
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->superAdmin->id);
    }

    #[Test]
    public function kasir_cannot_cancel_or_edit_purchase(): void
    {
        $purchase = $this->createPurchase(2);

        $this->actingAs($this->kasir)
            ->deleteJson(route('purchases.destroy', $purchase))
            ->assertForbidden();

        $this->actingAs($this->kasir)
            ->putJson(route('purchases.update', $purchase), [
                'payment_method' => 'cash',
                'items' => [['item_id' => $this->item->id, 'quantity' => 3]],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function cannot_cancel_purchase_twice(): void
    {
        $purchase = $this->createPurchase(2);
        app(PurchaseService::class)->cancel($purchase, $this->superAdmin->id);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('purchases.destroy', $purchase))
            ->assertStatus(422);
    }

    #[Test]
    public function cancel_fails_when_stock_insufficient_for_rollback(): void
    {
        $purchase = $this->createPurchase(5);
        $this->item->update(['stock' => 2]);

        $this->actingAs($this->superAdmin)
            ->deleteJson(route('purchases.destroy', $purchase))
            ->assertStatus(422);

        $this->assertTrue($purchase->fresh()->isCompleted());
        $this->assertSame(2, $this->item->fresh()->stock);
    }

    #[Test]
    public function edit_updates_financial_report_expense(): void
    {
        $purchase = $this->createPurchase(5);

        $before = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());
        $this->assertSame(50000.0, $before['purchases']['expense']);

        app(PurchaseService::class)->update($purchase, [
            'payment_method' => 'cash',
            'discount' => 10000,
            'items' => [['item_id' => $this->item->id, 'quantity' => 5]],
        ], $this->superAdmin->id);

        $after = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());
        $this->assertSame(40000.0, $after['purchases']['expense']);
    }

    #[Test]
    public function super_admin_can_update_purchase_via_http(): void
    {
        $purchase = $this->createPurchase(2);

        $this->actingAs($this->superAdmin)
            ->putJson(route('purchases.update', $purchase), [
                'payment_method' => 'cash',
                'discount' => 0,
                'items' => [['item_id' => $this->item->id, 'quantity' => 4]],
            ])
            ->assertOk();

        $this->assertSame(40000.0, (float) $purchase->fresh()->total);
        $this->assertSame(14, $this->item->fresh()->stock);
    }

    #[Test]
    public function cannot_edit_cancelled_purchase_via_http(): void
    {
        $purchase = $this->createPurchase(2);
        app(PurchaseService::class)->cancel($purchase, $this->superAdmin->id);

        $this->actingAs($this->superAdmin)
            ->putJson(route('purchases.update', $purchase), [
                'payment_method' => 'cash',
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ])
            ->assertNotFound();
    }

    #[Test]
    public function guest_cannot_cancel_or_edit_purchase(): void
    {
        $purchase = $this->createPurchase(2);

        $this->deleteJson(route('purchases.destroy', $purchase))->assertUnauthorized();
        $this->putJson(route('purchases.update', $purchase), [
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ])->assertUnauthorized();
    }
}
