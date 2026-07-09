<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use Database\Seeders\AthaMotorItemSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AthaMotorItemSeederTest extends TestCase
{
    #[Test]
    public function atha_motor_item_seeder_imports_excel_master_data(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $this->seed(AthaMotorItemSeeder::class);

        $this->assertGreaterThanOrEqual(900, Item::count());
        $this->assertGreaterThanOrEqual(100, ItemCategory::count());
        $this->assertNotNull(Item::where('name', 'like', '%ACCU GS GTZ5S%')->first());
    }
}
