<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Services\ItemExcelService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ItemImportExportTest extends TestCase
{
    private User $admin;

    private ItemCategory $category;

    private ItemUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->category = ItemCategory::create(['name' => 'Oli & Fluida']);
        $this->unit = ItemUnit::create(['name' => 'Liter', 'abbreviation' => 'L']);
    }

    #[Test]
    public function guest_cannot_export_items(): void
    {
        $this->get(route('items.export'))->assertRedirect(route('login'));
        $this->get(route('items.import.template'))->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_download_import_template_with_reference_sheets(): void
    {
        $path = storage_path('app/test-template.xlsx');

        $response = $this->actingAs($this->admin)->get(route('items.import.template'));
        $response->assertOk();
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);
        $this->assertNotNull($spreadsheet->getSheetByName('Import Barang'));
        $this->assertNotNull($spreadsheet->getSheetByName('Referensi Kategori'));
        $this->assertNotNull($spreadsheet->getSheetByName('Referensi Satuan'));

        $categorySheet = $spreadsheet->getSheetByName('Referensi Kategori');
        $this->assertSame('Oli & Fluida', $categorySheet->getCell('A2')->getValue());

        @unlink($path);
    }

    #[Test]
    public function admin_can_export_items(): void
    {
        Item::create([
            'code' => 'BRG-T-0001',
            'name' => 'Export Test Item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('items.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    #[Test]
    public function import_creates_items_from_template(): void
    {
        $service = app(ItemExcelService::class);
        $templateResponse = $service->downloadTemplate();

        ob_start();
        $templateResponse->sendContent();
        $binary = ob_get_clean();

        $path = storage_path('app/test-import.xlsx');
        file_put_contents($path, $binary);

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Import Barang');
        $sheet->setCellValue('A2', 'Barang Import Test');
        $sheet->setCellValue('B2', 'Oli & Fluida');
        $sheet->setCellValue('C2', 'Liter (L)');
        $sheet->setCellValue('D2', 5);
        $sheet->setCellValue('E2', 10000);
        $sheet->setCellValue('F2', 15000);
        $sheet->setCellValue('H2', 'Ya');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);

        $file = new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($this->admin)
            ->postJson(route('items.import'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('data.created', 1);

        $this->assertDatabaseHas('items', [
            'name' => 'Barang Import Test',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'stock' => 0,
        ]);

        @unlink($path);
    }

    #[Test]
    public function import_rejects_invalid_category(): void
    {
        $service = app(ItemExcelService::class);
        ob_start();
        $service->downloadTemplate()->sendContent();
        $binary = ob_get_clean();

        $path = storage_path('app/test-import-bad.xlsx');
        file_put_contents($path, $binary);

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Import Barang');
        $sheet->setCellValue('A2', 'Barang Invalid');
        $sheet->setCellValue('B2', 'Kategori Tidak Ada');
        $sheet->setCellValue('C2', 'Liter (L)');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        $file = new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($this->admin)
            ->postJson(route('items.import'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.skipped', 1);

        @unlink($path);
    }
}
