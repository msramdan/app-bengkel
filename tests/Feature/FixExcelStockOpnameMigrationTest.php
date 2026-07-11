<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Services\FixMislabelledExcelStockService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FixExcelStockOpnameMigrationTest extends TestCase
{
    #[Test]
    public function fixes_mislabelled_stock_opname_without_touching_existing_stock(): void
    {
        $user = User::factory()->create();
        $category = ItemCategory::create(['name' => 'Test Kategori Fix']);
        $unit = ItemUnit::create(['name' => 'Pieces', 'abbreviation' => 'pcs']);

        $needsFix = Item::create([
            'code' => 'BRG-FIX-001',
            'name' => 'Barang Perlu Koreksi',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 0,
            'stock_opname' => 12,
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'member_price' => 0,
            'is_active' => true,
        ]);

        $alreadyHasStock = Item::create([
            'code' => 'BRG-FIX-002',
            'name' => 'Barang Sudah Ada Stok',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'stock' => 5,
            'stock_opname' => 20,
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'member_price' => 0,
            'is_active' => true,
        ]);

        $result = app(FixMislabelledExcelStockService::class)->handle();

        $needsFix->refresh();
        $alreadyHasStock->refresh();

        $this->assertSame(1, $result['moved']);
        $this->assertSame(12, (int) $needsFix->stock);
        $this->assertSame(0, (int) $needsFix->stock_opname);
        $this->assertSame(5, (int) $alreadyHasStock->stock);
        $this->assertSame(0, (int) $alreadyHasStock->stock_opname);

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $needsFix->id,
            'type' => 'in',
            'quantity' => 12,
            'user_id' => $user->id,
        ]);
    }
}
