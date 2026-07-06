<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PruneLegacyMigrationsTest extends TestCase
{
    #[Test]
    public function prune_legacy_command_removes_obsolete_migration_files(): void
    {
        $directory = database_path('migrations');
        $legacyFile = $directory.DIRECTORY_SEPARATOR.'2026_07_01_142311_add_batch_no_to_stock_movements_table.php';

        File::put($legacyFile, '<?php // legacy test stub');

        try {
            Artisan::call('migrations:prune-legacy');

            $this->assertFileDoesNotExist($legacyFile);
            $this->assertStringContainsString('Dihapus:', Artisan::output());
        } finally {
            if (File::exists($legacyFile)) {
                File::delete($legacyFile);
            }
        }
    }

    #[Test]
    public function current_migration_set_has_no_legacy_files(): void
    {
        $legacyNames = [
            '2026_07_01_142311_add_batch_no_to_stock_movements_table.php',
            '2026_07_01_145854_create_bank_accounts_table.php',
            '2026_07_01_145342_create_purchases_table.php',
        ];

        foreach ($legacyNames as $filename) {
            $this->assertFileDoesNotExist(database_path('migrations/'.$filename));
        }
    }
}
