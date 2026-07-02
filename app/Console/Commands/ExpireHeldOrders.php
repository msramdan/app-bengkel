<?php

namespace App\Console\Commands;

use App\Services\TransactionService;
use Illuminate\Console\Command;

class ExpireHeldOrders extends Command
{
    protected $signature = 'transactions:expire-held';

    protected $description = 'Bersihkan open order draft yang sudah melewati batas waktu';

    public function handle(TransactionService $transactionService): int
    {
        $hours = (int) config('workshop.held_order_expire_hours', 8);
        $count = $transactionService->expireStaleHeldOrders();

        $this->info("Open order kedaluwarsa dibatalkan: {$count} (batas: {$hours} jam).");

        return self::SUCCESS;
    }
}
