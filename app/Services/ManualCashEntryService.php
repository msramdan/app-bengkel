<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\ManualCashEntry;
use App\Support\CodeGenerator;
use App\Support\PaymentMethodResolver;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ManualCashEntryService
{
    public function __construct(private PaymentMethodResolver $paymentResolver) {}

    /**
     * @param  array{
     *   category_id: int,
     *   amount: float|int|string,
     *   occurred_at: string,
     *   payment_method: string,
     *   bank_account_id?: int|null,
     *   description?: string|null
     * }  $payload
     */
    public function create(array $payload, string $type, int $userId): ManualCashEntry
    {
        if (! in_array($type, ['income', 'expense'], true)) {
            throw new InvalidArgumentException('Tipe entri kas tidak valid.');
        }

        return DB::transaction(function () use ($payload, $type, $userId) {
            $category = FinancialCategory::query()
                ->whereKey((int) $payload['category_id'])
                ->where('type', $type)
                ->where('is_active', true)
                ->first();

            if (! $category) {
                throw new InvalidArgumentException('Kategori tidak valid atau tidak aktif.');
            }

            $amount = round((float) $payload['amount'], 2);

            if ($amount <= 0) {
                throw new InvalidArgumentException('Nominal harus lebih dari 0.');
            }

            $allowedMethods = $type === 'expense'
                ? PaymentMethodResolver::purchaseMethods()
                : null;

            $payment = $this->paymentResolver->resolve(
                (string) $payload['payment_method'],
                isset($payload['bank_account_id']) ? (int) $payload['bank_account_id'] : null,
                $allowedMethods,
            );

            $prefix = $type === 'income'
                ? CodeGenerator::PREFIX_MANUAL_INCOME
                : CodeGenerator::PREFIX_MANUAL_EXPENSE;

            return ManualCashEntry::create([
                'entry_no' => CodeGenerator::nextFromTable($prefix, 'manual_cash_entries', 'entry_no'),
                'type' => $type,
                'category_id' => $category->id,
                'amount' => $amount,
                'occurred_at' => $payload['occurred_at'],
                'payment_method' => $payment['payment_method'],
                'bank_account_id' => $payment['bank_account_id'],
                'description' => isset($payload['description']) ? trim((string) $payload['description']) ?: null : null,
                'user_id' => $userId,
                'status' => 'completed',
            ]);
        });
    }

    public function cancel(ManualCashEntry $entry, int $userId): ManualCashEntry
    {
        return DB::transaction(function () use ($entry, $userId) {
            $locked = ManualCashEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || ! $locked->isCompleted()) {
                throw new InvalidArgumentException('Entri tidak valid atau sudah dibatalkan.');
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            return $locked->fresh(['category', 'user', 'bankAccount', 'cancelledByUser']);
        });
    }
}
