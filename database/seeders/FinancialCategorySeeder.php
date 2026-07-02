<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    public function run(): void
    {
        $income = [
            ['name' => 'Penjualan Barang Bekas', 'sort_order' => 10],
            ['name' => 'Penjualan Scrap / Besi', 'sort_order' => 20],
            ['name' => 'Penjualan Voucher / Lainnya', 'sort_order' => 30],
            ['name' => 'Jasa Luar / Titip', 'sort_order' => 40],
            ['name' => 'Lainnya', 'sort_order' => 99],
        ];

        foreach ($income as $row) {
            FinancialCategory::updateOrCreate(
                ['name' => $row['name'], 'type' => 'income'],
                ['is_active' => true, 'sort_order' => $row['sort_order']],
            );
        }

        $expense = [
            ['name' => 'Gaji Karyawan', 'sort_order' => 10],
            ['name' => 'Listrik & Utilitas', 'sort_order' => 20],
            ['name' => 'Sewa Tempat', 'sort_order' => 30],
            ['name' => 'ATK & Operasional', 'sort_order' => 40],
            ['name' => 'Bensin / Transport', 'sort_order' => 50],
            ['name' => 'Lainnya', 'sort_order' => 99],
        ];

        foreach ($expense as $row) {
            FinancialCategory::updateOrCreate(
                ['name' => $row['name'], 'type' => 'expense'],
                ['is_active' => true, 'sort_order' => $row['sort_order']],
            );
        }
    }
}
