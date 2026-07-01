<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\OilChangeReminderLog;
use App\Models\Transaction;
use App\Models\TransactionServiceLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class OilChangeReminderService
{
    public function __construct(
        private SettingService $settings,
        private HiwaWhatsAppService $hiwa,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->getBool('oil_change_reminder_enabled')
            && $this->hiwa->isEnabled();
    }

    /**
     * @return array{sent: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function sendDueReminders(?Carbon $asOf = null): array
    {
        $asOf ??= now();

        if (! $this->settings->getBool('oil_change_reminder_enabled')) {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => ['Pengingat ganti oli tidak aktif.']];
        }

        if (! $this->hiwa->isEnabled()) {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => ['Gateway HiWA belum aktif atau token belum diisi.']];
        }

        $intervalMonths = max(1, $this->settings->getInt('oil_change_reminder_months', 3));
        $candidates = $this->dueCandidates($intervalMonths, $asOf);

        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($candidates as $line) {
            $transaction = $line->transaction;
            $customer = $transaction->customer;

            if (! $customer || blank($customer->phone)) {
                $skipped++;

                continue;
            }

            if (OilChangeReminderLog::query()->where('transaction_id', $transaction->id)->exists()) {
                $skipped++;

                continue;
            }

            $dueAt = $transaction->created_at->copy()->addMonths($intervalMonths);
            if ($dueAt->isAfter($asOf)) {
                $skipped++;

                continue;
            }

            $message = $this->buildMessage($customer, $transaction, $intervalMonths);

            try {
                $result = $this->hiwa->send($customer->phone, $message);

                OilChangeReminderLog::create([
                    'customer_id' => $customer->id,
                    'transaction_id' => $transaction->id,
                    'phone' => $this->hiwa->normalizePhone($customer->phone),
                    'message' => $message,
                    'status' => $result['ok'] ? 'queued' : 'failed',
                    'hiwa_job_id' => $result['job_id'],
                    'due_at' => $dueAt,
                    'sent_at' => now(),
                ]);

                $sent++;
            } catch (InvalidArgumentException $e) {
                $failed++;
                $errors[] = "{$customer->name}: {$e->getMessage()}";
            }
        }

        return compact('sent', 'skipped', 'failed', 'errors');
    }

    /**
     * @return Collection<int, TransactionServiceLine>
     */
    private function dueCandidates(int $intervalMonths, Carbon $asOf): Collection
    {
        $serviceId = $this->settings->get('oil_change_workshop_service_id');

        $lines = TransactionServiceLine::query()
            ->whereHas('transaction', function ($query) {
                $query->where('status', 'completed');
            })
            ->when($serviceId, function ($query) use ($serviceId) {
                $query->where('workshop_service_id', $serviceId);
            }, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('service_name', 'like', '%ganti oli%')
                        ->orWhere('service_name', 'like', '%oli%');
                });
            })
            ->with(['transaction.customer'])
            ->get();

        return $lines
            ->groupBy(fn (TransactionServiceLine $line) => $line->transaction->customer_id)
            ->map(function (Collection $group) {
                return $group->sortByDesc(fn (TransactionServiceLine $line) => $line->transaction->created_at)->first();
            })
            ->filter(function (?TransactionServiceLine $line) use ($intervalMonths, $asOf) {
                if (! $line?->transaction) {
                    return false;
                }

                return $line->transaction->created_at
                    ->copy()
                    ->addMonths($intervalMonths)
                    ->lte($asOf);
            })
            ->values();
    }

    private function buildMessage(Customer $customer, Transaction $transaction, int $intervalMonths): string
    {
        $template = (string) config('reminders.oil_change_message');

        $replacements = [
            '{nama_pelanggan}' => $customer->name,
            '{nama_aplikasi}' => $this->settings->getString('app_name', brand_name()),
            '{interval_bulan}' => (string) $intervalMonths,
            '{tanggal_servis}' => $transaction->created_at?->format('d/m/Y') ?? '-',
            '{no_transaksi}' => $transaction->transaction_no,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
