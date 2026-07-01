<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HiwaWhatsAppService
{
    public function __construct(private SettingService $settings) {}

    public function isEnabled(): bool
    {
        return $this->settings->getBool('hiwa_enabled')
            && $this->settings->getString('hiwa_token_device') !== '';
    }

    /**
     * @return array{ok: bool, job_id: string|null, message: string, quota: array<string, mixed>|null, http_status: int}
     */
    public function send(string $to, string $message): array
    {
        $token = $this->settings->getString('hiwa_token_device');
        if ($token === '') {
            throw new InvalidArgumentException('Token device HiWA belum dikonfigurasi.');
        }

        $phone = $this->normalizePhone($to);
        if ($phone === '') {
            throw new InvalidArgumentException('Nomor WhatsApp tidak valid.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($this->messagesUrl(), [
                'token_device' => $token,
                'to' => $phone,
                'message' => $message,
            ]);

        return $this->parseResponse($response);
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    private function messagesUrl(): string
    {
        return rtrim(config('hiwa.base_url'), '/').config('hiwa.messages_path');
    }

    /**
     * @return array{ok: bool, job_id: string|null, message: string, quota: array<string, mixed>|null, http_status: int}
     */
    private function parseResponse(Response $response): array
    {
        $body = $response->json() ?? [];
        $ok = (bool) ($body['ok'] ?? false);

        if (! $response->successful() && ! $ok) {
            $message = (string) ($body['message'] ?? 'Gagal menghubungi HiWA API.');

            if (isset($body['errors']) && is_array($body['errors'])) {
                $first = collect($body['errors'])->flatten()->first();
                if ($first) {
                    $message = (string) $first;
                }
            }

            throw new InvalidArgumentException($message);
        }

        return [
            'ok' => $ok,
            'job_id' => isset($body['job_id']) ? (string) $body['job_id'] : null,
            'message' => (string) ($body['message'] ?? 'Pesan sudah masuk antrian pengiriman.'),
            'quota' => is_array($body['quota'] ?? null) ? $body['quota'] : null,
            'http_status' => $response->status(),
        ];
    }
}
