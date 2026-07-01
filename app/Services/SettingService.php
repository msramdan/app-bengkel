<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingService
{
    private const CACHE_KEY = 'app.settings.all';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $defaults = config('settings.defaults', []);
            $stored = Setting::query()->pluck('value', 'key')->all();

            $merged = [];
            foreach ($defaults as $key => $default) {
                $merged[$key] = $this->decodeValue($key, $stored[$key] ?? $default);
            }

            return $merged;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_string($value) ? $value : (string) $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, config('settings.defaults', []))) {
                continue;
            }

            $encoded = $this->encodeValue($key, $value);

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $encoded],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function encodeValue(string $key, mixed $value): ?string
    {
        if (in_array($key, config('settings.encrypted_keys', []), true)) {
            $string = is_string($value) ? trim($value) : '';

            return $string === '' ? null : Crypt::encryptString($string);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function decodeValue(string $key, mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return config("settings.defaults.{$key}");
        }

        if (in_array($key, config('settings.encrypted_keys', []), true)) {
            try {
                return Crypt::decryptString((string) $raw);
            } catch (\Throwable) {
                return '';
            }
        }

        if (in_array($key, [
            'hiwa_enabled',
            'oil_change_reminder_enabled',
        ], true)) {
            return $raw === '1' || $raw === 1 || $raw === true;
        }

        if (in_array($key, [
            'oil_change_reminder_months',
            'oil_change_workshop_service_id',
        ], true)) {
            return $raw === null || $raw === '' ? null : (int) $raw;
        }

        return (string) $raw;
    }
}
