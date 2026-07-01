<?php

namespace App\Console\Commands;

use App\Services\OilChangeReminderService;
use Illuminate\Console\Command;

class SendOilChangeReminders extends Command
{
    protected $signature = 'reminders:oil-change';

    protected $description = 'Kirim pengingat ganti oli ke pelanggan via HiWA WhatsApp';

    public function handle(OilChangeReminderService $service): int
    {
        $result = $service->sendDueReminders();

        $this->info("Terkirim: {$result['sent']}, dilewati: {$result['skipped']}, gagal: {$result['failed']}");

        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return self::SUCCESS;
    }
}
