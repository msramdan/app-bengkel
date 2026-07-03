<?php

namespace App\Http\Controllers;

use App\Models\WorkshopService;
use App\Services\OilChangeReminderService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        private SettingService $settings,
        private OilChangeReminderService $reminders,
    ) {
        $this->middleware('permission:settings view')->only(['edit']);
        $this->middleware('permission:settings edit')->only(['update', 'runReminders']);
    }

    public function edit(): View
    {
        $values = $this->settings->all();
        $token = $values['hiwa_token_device'] ?? '';
        $values['hiwa_token_device_masked'] = $token !== '' ? str_repeat('•', min(20, strlen($token))) : '';

        return view('settings.edit', [
            'settings' => $values,
            'workshopServices' => WorkshopService::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_tagline' => ['nullable', 'string', 'max:255'],
            'app_description' => ['nullable', 'string', 'max:2000'],
            'hiwa_enabled' => ['nullable', 'boolean'],
            'hiwa_token_device' => ['nullable', 'string', 'max:500'],
            'oil_change_reminder_enabled' => ['nullable', 'boolean'],
            'oil_change_reminder_months' => ['required', 'integer', 'min:1', 'max:24'],
            'oil_change_workshop_service_ids' => ['nullable', 'array'],
            'oil_change_workshop_service_ids.*' => ['integer', 'exists:workshop_services,id'],
        ]);

        $serviceIds = collect($validated['oil_change_workshop_service_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $payload = [
            'app_name' => $validated['app_name'],
            'app_tagline' => $validated['app_tagline'] ?? '',
            'app_description' => $validated['app_description'] ?? '',
            'hiwa_enabled' => $request->boolean('hiwa_enabled'),
            'oil_change_reminder_enabled' => $request->boolean('oil_change_reminder_enabled'),
            'oil_change_reminder_months' => (int) $validated['oil_change_reminder_months'],
            'oil_change_workshop_service_ids' => $serviceIds,
        ];

        $newToken = trim((string) ($validated['hiwa_token_device'] ?? ''));
        if ($newToken !== '') {
            $payload['hiwa_token_device'] = $newToken;
        }

        $this->settings->setMany($payload);

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }

    public function runReminders(): JsonResponse
    {
        $result = $this->reminders->sendDueReminders();

        return response()->json([
            'ok' => true,
            'message' => "Pengingat diproses: {$result['sent']} terkirim, {$result['skipped']} dilewati, {$result['failed']} gagal.",
            'data' => $result,
        ]);
    }
}
