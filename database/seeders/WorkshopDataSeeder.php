<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkshopService;
use App\Services\StockService;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

class WorkshopDataSeeder extends Seeder
{
    public function run(): void
    {
        $userId = (int) (User::query()->value('id') ?? 1);
        $stockService = app(StockService::class);
        $transactionService = app(TransactionService::class);

        BankAccount::firstOrCreate(
            ['bank_name' => 'BRI', 'account_number' => '1234567890'],
            ['account_name' => 'Atha Motor', 'is_active' => true]
        );

        // ── Pelanggan ──
        $customers = [
            ['name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi.santoso@gmail.com', 'address' => 'Jl. Merdeka No. 12, Bandung', 'notes' => 'Pelanggan rutin servis berkala'],
            ['name' => 'Siti Aminah', 'phone' => '082198765432', 'email' => null, 'address' => 'Jl. Sudirman No. 45, Bandung', 'notes' => null],
            ['name' => 'Andi Pratama', 'phone' => '085612345678', 'email' => 'andi.pratama@yahoo.com', 'address' => 'Komplek Griya Asri Blok C7', 'notes' => 'Mobil Avanza putih'],
            ['name' => 'Dewi Lestari', 'phone' => '081112223334', 'email' => 'dewi.lestari@gmail.com', 'address' => 'Jl. Cihampelas No. 88', 'notes' => 'Sering ganti oli'],
            ['name' => 'Rizky Hidayat', 'phone' => '087711223344', 'email' => null, 'address' => 'Perumahan Melati Indah RT 03/05', 'notes' => 'Motor Yamaha NMAX'],
            ['name' => 'PT Maju Jaya Motor', 'phone' => '0221234567', 'email' => 'admin@majujayamotor.co.id', 'address' => 'Jl. Industri Raya No. 100', 'notes' => 'Pelanggan korporat'],
        ];

        foreach ($customers as $row) {
            $customer = Customer::firstOrNew(['name' => $row['name']]);
            if (! $customer->exists) {
                $customer->code = Customer::generateCode();
            }
            $customer->fill($row);
            $customer->save();
        }

        // ── Teknisi ──
        $technicians = [
            ['name' => 'Ahmad Fauzi', 'phone' => '081320001001', 'email' => 'ahmad.fauzi@athamotor.local', 'specialty' => 'Mesin & Transmisi', 'commission_percent' => 15, 'is_active' => true, 'notes' => 'Senior teknisi'],
            ['name' => 'Eko Wijaya', 'phone' => '081320001002', 'email' => null, 'specialty' => 'Kelistrikan & AC', 'commission_percent' => 12, 'is_active' => true, 'notes' => null],
            ['name' => 'Hendra Gunawan', 'phone' => '081320001003', 'email' => 'hendra.g@athamotor.local', 'specialty' => 'Rem & Understel', 'commission_percent' => 10, 'is_active' => true, 'notes' => null],
            ['name' => 'Yoga Pratama', 'phone' => '081320001004', 'email' => null, 'specialty' => 'Tune Up Motor', 'commission_percent' => 10, 'is_active' => false, 'notes' => 'Nonaktif sementara'],
        ];

        foreach ($technicians as $row) {
            $technician = Technician::firstOrNew(['name' => $row['name']]);
            if (! $technician->exists) {
                $technician->code = Technician::generateCode();
            }
            $technician->fill($row);
            $technician->save();
        }

        // ── Kategori & Satuan ──
        $categoryIds = [];
        foreach ([
            'Oli & Fluida' => 'Oli mesin, oli rem, coolant, dan cairan kendaraan',
            'Filter' => 'Filter oli, filter udara, filter AC',
            'Sparepart Mesin' => 'Kampas rem, busi, aki, belt, dan komponen mesin',
            'Aksesoris' => 'Wiper, lampu, dan aksesoris kendaraan',
        ] as $name => $description) {
            $categoryIds[$name] = ItemCategory::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            )->id;
        }

        $unitIds = [];
        foreach ([
            'Pieces' => 'pcs',
            'Liter' => 'L',
            'Set' => 'set',
            'Botol' => 'btl',
        ] as $name => $abbreviation) {
            $unitIds[$name] = ItemUnit::firstOrCreate(
                ['name' => $name],
                ['abbreviation' => $abbreviation]
            )->id;
        }

        // ── Barang ──
        $items = [
            ['name' => 'Oli Mesin 1L Shell Helix', 'category' => 'Oli & Fluida', 'unit' => 'Liter', 'stock_opname' => 10, 'purchase_price' => 45000, 'selling_price' => 65000, 'stock_in' => 25],
            ['name' => 'Oli Mesin 1L Pertamina Enduro', 'category' => 'Oli & Fluida', 'unit' => 'Liter', 'stock_opname' => 10, 'purchase_price' => 38000, 'selling_price' => 55000, 'stock_in' => 30],
            ['name' => 'Oli Rem DOT 4', 'category' => 'Oli & Fluida', 'unit' => 'Botol', 'stock_opname' => 5, 'purchase_price' => 25000, 'selling_price' => 40000, 'stock_in' => 12],
            ['name' => 'Coolant Radiator 1L', 'category' => 'Oli & Fluida', 'unit' => 'Liter', 'stock_opname' => 8, 'purchase_price' => 22000, 'selling_price' => 35000, 'stock_in' => 15],
            ['name' => 'Filter Oli Avanza/Xenia', 'category' => 'Filter', 'unit' => 'Pieces', 'stock_opname' => 5, 'purchase_price' => 18000, 'selling_price' => 30000, 'stock_in' => 20],
            ['name' => 'Filter Udara Avanza', 'category' => 'Filter', 'unit' => 'Pieces', 'stock_opname' => 5, 'purchase_price' => 35000, 'selling_price' => 55000, 'stock_in' => 8],
            ['name' => 'Kampas Rem Depan Avanza', 'category' => 'Sparepart Mesin', 'unit' => 'Set', 'stock_opname' => 3, 'purchase_price' => 120000, 'selling_price' => 175000, 'stock_in' => 6],
            ['name' => 'Busi NGK Iridium', 'category' => 'Sparepart Mesin', 'unit' => 'Pieces', 'stock_opname' => 10, 'purchase_price' => 45000, 'selling_price' => 70000, 'stock_in' => 24],
            ['name' => 'Aki GS Astra GTZ5S', 'category' => 'Sparepart Mesin', 'unit' => 'Pieces', 'stock_opname' => 2, 'purchase_price' => 185000, 'selling_price' => 250000, 'stock_in' => 4],
            ['name' => 'V-Belt Avanza', 'category' => 'Sparepart Mesin', 'unit' => 'Pieces', 'stock_opname' => 3, 'purchase_price' => 65000, 'selling_price' => 95000, 'stock_in' => 5],
            ['name' => 'Wiper Blade 14"', 'category' => 'Aksesoris', 'unit' => 'Pieces', 'stock_opname' => 5, 'purchase_price' => 28000, 'selling_price' => 45000, 'stock_in' => 3],
            ['name' => 'Lampu LED H4', 'category' => 'Aksesoris', 'unit' => 'Pieces', 'stock_opname' => 4, 'purchase_price' => 75000, 'selling_price' => 110000, 'stock_in' => 0],
        ];

        foreach ($items as $row) {
            $stockInQty = $row['stock_in'];
            $category = $row['category'];
            $unit = $row['unit'];
            unset($row['stock_in'], $row['category'], $row['unit']);

            $item = Item::firstOrNew(['name' => $row['name']]);
            if (! $item->exists) {
                $item->code = Item::generateCode();
            }
            $item->fill([
                ...$row,
                'category_id' => $categoryIds[$category],
                'unit_id' => $unitIds[$unit],
                'is_active' => true,
                'stock' => $item->exists ? $item->stock : 0,
            ]);
            $item->save();

            $hasStockIn = StockMovement::query()
                ->where('item_id', $item->id)
                ->where('type', 'in')
                ->where('notes', 'Stok awal dari seeder')
                ->exists();

            if (! $hasStockIn && $stockInQty > 0) {
                $stockService->stockIn(
                    $item->id,
                    $stockInQty,
                    $userId,
                    null,
                    'Stok awal dari seeder'
                );
            }
        }

        // ── Contoh stok keluar (sekali saja) ──
        $oliShell = Item::where('name', 'Oli Mesin 1L Shell Helix')->first();
        if ($oliShell && ! StockMovement::where('notes', 'Contoh pemakaian servis — oli shell')->exists()) {
            $stockService->stockOut($oliShell->id, 3, $userId, null, 'Contoh pemakaian servis — oli shell');
        }

        $filterOli = Item::where('name', 'Filter Oli Avanza/Xenia')->first();
        if ($filterOli && ! StockMovement::where('notes', 'Contoh pemakaian servis — filter oli')->exists()) {
            $stockService->stockOut($filterOli->id, 2, $userId, null, 'Contoh pemakaian servis — filter oli');
        }

        // ── Master Jasa Servis ──
        $workshopServices = [
            ['name' => 'Ganti Oli + Filter', 'description' => 'Servis ganti oli mesin dan filter oli', 'price' => 75000],
            ['name' => 'Tune Up Motor', 'description' => 'Tune up ringan motor', 'price' => 100000],
            ['name' => 'Servis Rem Depan', 'description' => 'Bongkar pasang kampas rem depan', 'price' => 150000],
            ['name' => 'Servis AC Mobil', 'description' => 'Cek dan isi freon AC', 'price' => 200000],
            ['name' => 'Balancing & Spooring', 'description' => 'Penyetelan roda', 'price' => 120000],
        ];

        foreach ($workshopServices as $row) {
            $service = WorkshopService::firstOrNew(['name' => $row['name']]);
            if (! $service->exists) {
                $service->code = WorkshopService::generateCode();
            }
            $service->fill([...$row, 'is_active' => true]);
            $service->save();
        }

        // ── Contoh transaksi demo ──
        $customer = Customer::where('name', 'Budi Santoso')->first();
        $technician = Technician::where('name', 'Ahmad Fauzi')->where('is_active', true)->first();
        $oliShell = Item::where('name', 'Oli Mesin 1L Shell Helix')->first();
        $gantiOli = WorkshopService::where('name', 'Ganti Oli + Filter')->first();

        if ($customer && $technician && $oliShell && $gantiOli && $oliShell->stock >= 1) {
            $hasDemoTx = \App\Models\Transaction::where('notes', 'Contoh transaksi gabungan dari seeder')->exists();

            if (! $hasDemoTx) {
                $transactionService->create([
                    'customer_id' => $customer->id,
                    'technician_id' => $technician->id,
                    'notes' => 'Contoh transaksi gabungan dari seeder',
                    'items' => [['item_id' => $oliShell->id, 'quantity' => 1]],
                    'services' => [['workshop_service_id' => $gantiOli->id, 'quantity' => 1]],
                ], $userId);
            }
        }

        $customer2 = Customer::where('name', 'Siti Aminah')->first();
        $tuneUp = WorkshopService::where('name', 'Tune Up Motor')->first();

        if ($customer2 && $technician && $tuneUp) {
            $hasServiceTx = \App\Models\Transaction::where('notes', 'Contoh transaksi servis dari seeder')->exists();

            if (! $hasServiceTx) {
                $transactionService->create([
                    'customer_id' => $customer2->id,
                    'technician_id' => $technician->id,
                    'notes' => 'Contoh transaksi servis dari seeder',
                    'services' => [['workshop_service_id' => $tuneUp->id, 'quantity' => 1]],
                ], $userId);
            }
        }

        $busi = Item::where('name', 'Busi NGK Iridium')->first();
        if ($customer && $busi && $busi->stock >= 2) {
            $hasSaleTx = \App\Models\Transaction::where('notes', 'Contoh penjualan sparepart dari seeder')->exists();

            if (! $hasSaleTx) {
                $transactionService->create([
                    'customer_id' => $customer->id,
                    'notes' => 'Contoh penjualan sparepart dari seeder',
                    'items' => [['item_id' => $busi->id, 'quantity' => 2]],
                ], $userId);
            }
        }
    }
}
