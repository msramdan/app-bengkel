<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Item;
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
        $demoItem = Item::query()->where('name', 'like', '%ACCU GS GTZ5S%')->first()
            ?? Item::query()->where('is_active', true)->orderBy('id')->first();
        $gantiOli = WorkshopService::where('name', 'Ganti Oli + Filter')->first();

        if ($customer && $technician && $demoItem && $gantiOli) {
            if (! StockMovement::query()
                ->where('item_id', $demoItem->id)
                ->where('type', 'in')
                ->where('notes', 'Stok awal demo transaksi')
                ->exists()) {
                $stockService->stockIn($demoItem->id, 5, $userId, null, 'Stok awal demo transaksi');
                $demoItem->refresh();
            }

            $hasDemoTx = \App\Models\Transaction::where('notes', 'Contoh transaksi gabungan dari seeder')->exists();

            if (! $hasDemoTx && $demoItem->stock >= 1) {
                $transactionService->create([
                    'customer_id' => $customer->id,
                    'technician_id' => $technician->id,
                    'notes' => 'Contoh transaksi gabungan dari seeder',
                    'items' => [['item_id' => $demoItem->id, 'quantity' => 1]],
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

        $busi = Item::query()->where('name', 'like', '%BUSI%')->where('is_active', true)->first();
        if ($customer && $busi) {
            if (! StockMovement::query()
                ->where('item_id', $busi->id)
                ->where('type', 'in')
                ->where('notes', 'Stok awal demo penjualan')
                ->exists()) {
                $stockService->stockIn($busi->id, 5, $userId, null, 'Stok awal demo penjualan');
                $busi->refresh();
            }

            $hasSaleTx = \App\Models\Transaction::where('notes', 'Contoh penjualan sparepart dari seeder')->exists();

            if (! $hasSaleTx && $busi->stock >= 2) {
                $transactionService->create([
                    'customer_id' => $customer->id,
                    'notes' => 'Contoh penjualan sparepart dari seeder',
                    'items' => [['item_id' => $busi->id, 'quantity' => 2]],
                ], $userId);
            }
        }
    }
}
