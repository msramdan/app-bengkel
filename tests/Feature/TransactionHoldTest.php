<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionHoldTest extends TestCase
{
    private User $user;

    private Customer $customer;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('Super Admin');

        $category = ItemCategory::create(['name' => 'Hold Cat']);
        $unit = ItemUnit::create(['name' => 'Pcs', 'abbreviation' => 'pcs']);

        $this->item = Item::create([
            'code' => 'BRG-HOLD-0001',
            'name' => 'Hold Item',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 2,
            'stock_opname' => 1,
            'purchase_price' => 10000,
            'selling_price' => 25000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'code' => 'PLG-HOLD-0001',
            'name' => 'Hold Customer',
        ]);
    }

    #[Test]
    public function draft_save_does_not_deduct_stock(): void
    {
        $tx = app(TransactionService::class)->hold([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $this->assertSame('held', $tx->status);
        $this->assertSame(2, $this->item->fresh()->stock);
        $this->assertSame(0, StockMovement::where('item_id', $this->item->id)->count());
    }

    #[Test]
    public function multiple_drafts_can_reserve_same_items_without_stock_deduction(): void
    {
        app(TransactionService::class)->hold([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $second = app(TransactionService::class)->hold([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $this->assertSame('held', $second->status);
        $this->assertSame(2, $this->item->fresh()->stock);
        $this->assertSame(2, Transaction::where('status', 'held')->count());
    }

    #[Test]
    public function complete_draft_deducts_stock_on_submit(): void
    {
        $held = app(TransactionService::class)->hold([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $completed = app(TransactionService::class)->completeHeld($held, [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);

        $this->assertSame('completed', $completed->status);
        $this->assertSame(1, $this->item->fresh()->stock);
        $this->assertSame(1, StockMovement::where('item_id', $this->item->id)->where('type', 'out')->count());
    }

    #[Test]
    public function submit_fails_when_stock_insufficient_with_clear_error(): void
    {
        app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stok "Hold Item" tidak mencukupi');

        app(TransactionService::class)->create([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ], $this->user->id);
    }

    #[Test]
    public function cancel_draft_does_not_change_stock(): void
    {
        $held = app(TransactionService::class)->hold([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        app(TransactionService::class)->cancelHeld($held, $this->user->id);

        $this->assertSame('cancelled', $held->fresh()->status);
        $this->assertSame(2, $this->item->fresh()->stock);
    }

    #[Test]
    public function concurrent_submits_prevent_overselling(): void
    {
        $success = 0;
        $errors = 0;

        for ($i = 0; $i < 3; $i++) {
            try {
                DB::transaction(function () use (&$success) {
                    app(TransactionService::class)->create([
                        'customer_mode' => 'existing',
                        'customer_id' => $this->customer->id,
                        'payment_method' => 'cash',
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
    }

    #[Test]
    public function kasir_can_save_draft_and_complete_via_http(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $holdResponse = $this->actingAs($kasir)->postJson(route('transactions.hold'), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ]);

        $holdResponse->assertOk();
        $heldId = $holdResponse->json('data.id');
        $this->assertSame(2, $this->item->fresh()->stock);

        $completeResponse = $this->actingAs($kasir)->postJson(route('transactions.complete', $heldId), [
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'payment_method' => 'cash',
            'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
        ]);

        $completeResponse->assertOk();
        $this->assertSame('completed', Transaction::find($heldId)->status);
        $this->assertSame(1, $this->item->fresh()->stock);
    }

    #[Test]
    public function stale_drafts_are_auto_cancelled_without_stock_change(): void
    {
        $held = app(TransactionService::class)->hold([
            'customer_mode' => 'existing',
            'customer_id' => $this->customer->id,
            'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
        ], $this->user->id);

        $held->update(['held_at' => now()->subHours(9)]);

        $count = app(TransactionService::class)->expireStaleHeldOrders();

        $this->assertSame(1, $count);
        $this->assertSame('cancelled', $held->fresh()->status);
        $this->assertSame(2, $this->item->fresh()->stock);
    }
}
