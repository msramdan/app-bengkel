<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OilChangeReminderLog;
use App\Models\Transaction;
use App\Models\TransactionServiceLine;
use App\Models\User;
use App\Models\WorkshopService;
use App\Services\HiwaWhatsAppService;
use App\Services\OilChangeReminderService;
use App\Services\SettingService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationSettingsTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    #[Test]
    public function super_admin_can_open_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Pengaturan Aplikasi')
            ->assertSee('Gateway WhatsApp');
    }

    #[Test]
    public function kasir_cannot_access_settings(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $this->actingAs($kasir)
            ->get(route('settings.edit'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_update_app_branding_settings(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'app_name' => 'Bengkel Jaya',
                'app_tagline' => 'Servis Terpercaya',
                'app_description' => 'Deskripsi bengkel.',
                'oil_change_reminder_months' => 2,
            ])
            ->assertRedirect(route('settings.edit'));

        $settings = app(SettingService::class);
        $this->assertSame('Bengkel Jaya', $settings->getString('app_name'));
        $this->assertSame('Servis Terpercaya', $settings->getString('app_tagline'));
        $this->assertSame(2, $settings->getInt('oil_change_reminder_months'));
        $this->assertSame('Bengkel Jaya', brand_name());
    }

    #[Test]
    public function hiwa_service_sends_message_to_api(): void
    {
        Http::fake([
            'hiwa.my.id/*' => Http::response([
                'ok' => true,
                'job_id' => 'test-job-123',
                'message' => 'Pesan sudah masuk antrian pengiriman.',
                'quota' => ['messages_remaining' => 99, 'messages_limit_per_month' => 100],
            ], 200),
        ]);

        app(SettingService::class)->setMany([
            'hiwa_enabled' => true,
            'hiwa_token_device' => 'secret-token',
        ]);

        $result = app(HiwaWhatsAppService::class)->send('081234567890', 'Halo test');

        $this->assertTrue($result['ok']);
        $this->assertSame('test-job-123', $result['job_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hiwa.my.id/api/v1/messages'
                && $request['token_device'] === 'secret-token'
                && $request['to'] === '6281234567890'
                && $request['message'] === 'Halo test';
        });
    }

    #[Test]
    public function oil_change_reminder_sends_whatsapp_for_due_customers(): void
    {
        Http::fake([
            'hiwa.my.id/*' => Http::response([
                'ok' => true,
                'job_id' => 'reminder-job-1',
                'message' => 'Pesan sudah masuk antrian pengiriman.',
            ], 200),
        ]);

        $service = WorkshopService::create([
            'code' => 'JSV-OIL-01',
            'name' => 'Ganti Oli + Filter',
            'price' => 75000,
            'is_active' => true,
        ]);

        app(SettingService::class)->setMany([
            'app_name' => 'Atha Motor',
            'hiwa_enabled' => true,
            'hiwa_token_device' => 'token-abc',
            'oil_change_reminder_enabled' => true,
            'oil_change_reminder_months' => 3,
            'oil_change_workshop_service_id' => $service->id,
        ]);

        $customer = Customer::create([
            'code' => 'PLG-REM-01',
            'name' => 'Budi Reminder',
            'phone' => '081298765432',
        ]);

        $transaction = Transaction::create([
            'transaction_no' => 'SRV-TEST-0001',
            'type' => 'service',
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'subtotal_items' => 0,
            'subtotal_services' => 75000,
            'discount' => 0,
            'total' => 75000,
            'technician_commission' => 0,
            'owner_service_share' => 75000,
            'owner_items_share' => 0,
            'owner_total_share' => 75000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);
        $transaction->created_at = Carbon::now()->subMonths(3)->subDay();
        $transaction->updated_at = $transaction->created_at;
        $transaction->saveQuietly();

        TransactionServiceLine::create([
            'transaction_id' => $transaction->id,
            'workshop_service_id' => $service->id,
            'service_code' => $service->code,
            'service_name' => $service->name,
            'quantity' => 1,
            'unit_price' => 75000,
            'subtotal' => 75000,
        ]);

        $result = app(OilChangeReminderService::class)->sendDueReminders();

        $this->assertSame(1, $result['sent']);
        $this->assertDatabaseHas('oil_change_reminder_logs', [
            'customer_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'hiwa_job_id' => 'reminder-job-1',
        ]);
    }
}
