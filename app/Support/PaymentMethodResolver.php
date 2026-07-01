<?php

namespace App\Support;

use App\Models\BankAccount;
use InvalidArgumentException;

class PaymentMethodResolver
{
    /**
     * @param  list<string>|null  $allowedMethods
     * @return array{payment_method: string, bank_account_id: int|null}
     */
    public function resolve(string $method, ?int $bankAccountId = null, ?array $allowedMethods = null): array
    {
        $methods = $allowedMethods ?? array_keys(config('workshop.payment_methods', []));

        if (! in_array($method, $methods, true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        if ($method === 'transfer') {
            if (empty($bankAccountId)) {
                throw new InvalidArgumentException('Pilih akun bank untuk pembayaran transfer.');
            }

            $account = BankAccount::query()
                ->where('id', $bankAccountId)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw new InvalidArgumentException('Akun bank tidak valid atau tidak aktif.');
            }

            return [
                'payment_method' => $method,
                'bank_account_id' => $account->id,
            ];
        }

        if (! empty($bankAccountId)) {
            throw new InvalidArgumentException('Akun bank hanya untuk metode transfer.');
        }

        return [
            'payment_method' => $method,
            'bank_account_id' => null,
        ];
    }

    public static function label(?string $method): string
    {
        return config('workshop.payment_methods.'.$method)
            ?? config('workshop.purchase_payment_methods.'.$method, $method ?? '-');
    }

    /**
     * @return list<string>
     */
    public static function purchaseMethods(): array
    {
        return array_keys(config('workshop.purchase_payment_methods', []));
    }
}
