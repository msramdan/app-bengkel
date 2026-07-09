<?php

namespace Database\Seeders;

use App\Services\ItemExcelService;
use Illuminate\Database\Seeder;

class AthaMotorItemSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/atha-motor-items.xlsx');

        $result = app(ItemExcelService::class)->seedFromPath($path);

        $this->command?->info("Barang Atha Motor: {$result['created']} ditambahkan, {$result['skipped']} dilewati.");

        if ($result['errors'] !== []) {
            $preview = array_slice($result['errors'], 0, 5);
            $this->command?->warn('Contoh peringatan import:');
            foreach ($preview as $error) {
                $this->command?->warn("  - {$error}");
            }

            if (count($result['errors']) > 5) {
                $remaining = count($result['errors']) - 5;
                $this->command?->warn("  ... dan {$remaining} peringatan lainnya.");
            }
        }
    }
}
