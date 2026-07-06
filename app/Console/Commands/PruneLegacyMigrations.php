<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PruneLegacyMigrations extends Command
{
    protected $signature = 'migrations:prune-legacy';

    protected $description = 'Hapus file migration lama yang sudah digabung ke create_* (untuk deploy manual)';

    /**
     * Migration yang sudah tidak dipakai sejak konsolidasi 84cb2cd.
     *
     * @var list<string>
     */
    private const LEGACY_FILES = [
        '2026_07_01_142311_add_batch_no_to_stock_movements_table.php',
        '2026_07_01_142613_add_photo_to_technicians_and_items_table.php',
        '2026_07_01_143109_rename_min_stock_to_stock_opname_on_items_table.php',
        '2026_07_01_145854_create_bank_accounts_table.php',
        '2026_07_01_145342_create_purchases_table.php',
        '2026_07_01_145343_create_purchase_items_table.php',
        '2026_07_01_145855_add_payment_fields_to_transactions_and_purchases_table.php',
        '2026_07_01_160000_set_default_technician_commission_percent.php',
        '2026_07_01_180000_add_customer_name_and_nullable_customer_on_transactions.php',
        '2026_07_01_190000_add_member_price_to_items_table.php',
        '2026_07_01_200000_add_is_member_to_customers_table.php',
        '2026_07_01_210000_add_held_status_to_transactions_table.php',
        '2026_07_03_100000_remove_held_open_order_support.php',
        '2026_07_03_120000_add_cash_payment_fields_to_transactions_table.php',
        '2026_07_03_150000_add_cancelled_audit_to_transactions_table.php',
    ];

    public function handle(): int
    {
        $directory = database_path('migrations');
        $removed = 0;

        foreach (self::LEGACY_FILES as $filename) {
            $path = $directory.DIRECTORY_SEPARATOR.$filename;

            if (! File::exists($path)) {
                continue;
            }

            File::delete($path);
            $this->line("Dihapus: {$filename}");
            $removed++;
        }

        if ($removed === 0) {
            $this->info('Tidak ada migration lama. Folder sudah bersih.');
        } else {
            $this->info("{$removed} migration lama dihapus. Lanjutkan: php artisan migrate:fresh --seed --force");
        }

        return self::SUCCESS;
    }
}
