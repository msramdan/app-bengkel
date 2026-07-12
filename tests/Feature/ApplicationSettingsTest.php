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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Gateway WhatsApp')
            ->assertSee('Alamat Bengkel')
            ->assertSee('WhatsApp Bengkel')
            ->assertSee('Logo Bengkel');
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
    public function admin_can_update_company_address_whatsapp_and_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo-bengkel.png', 200, 200);

        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'app_name' => 'Atha Motor',
                'oil_change_reminder_months' => 3,
                'company_address' => 'Jl. Merdeka No. 10, Bandung',
                'company_whatsapp' => '081234567890',
                'photo' => $file,
            ])
            ->assertRedirect(route('settings.edit'));

        $settings = app(SettingService::class);
        $this->assertSame('Jl. Merdeka No. 10, Bandung', $settings->getString('company_address'));
        $this->assertSame('081234567890', $settings->getString('company_whatsapp'));
        $this->assertNotSame('', $settings->getString('company_logo'));
        $this->assertTrue(Storage::disk('public')->exists($settings->getString('company_logo')));
        $this->assertSame('Jl. Merdeka No. 10, Bandung', brand_address());
        $this->assertSame('081234567890', brand_whatsapp());
        $this->assertTrue(brand_has_custom_logo());
    }

    #[Test]
    public function updating_settings_without_logo_keeps_existing_logo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'app_name' => 'Atha Motor',
                'oil_change_reminder_months' => 3,
                'company_address' => 'Alamat Lama',
                'company_whatsapp' => '081111111111',
                'photo' => UploadedFile::fake()->image('logo1.png'),
            ])
            ->assertRedirect(route('settings.edit'));

        $logoPath = app(SettingService::class)->getString('company_logo');

        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'app_name' => 'Atha Motor',
                'oil_change_reminder_months' => 3,
                'company_address' => 'Alamat Baru',
                'company_whatsapp' => '082222222222',
            ])
            ->assertRedirect(route('settings.edit'));

        $settings = app(SettingService::class);
        $this->assertSame('Alamat Baru', $settings->getString('company_address'));
        $this->assertSame('082222222222', $settings->getString('company_whatsapp'));
        $this->assertSame($logoPath, $settings->getString('company_logo'));
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
            'oil_change_workshop_service_ids' => [$service->id],
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

    #[Test]
    public function oil_change_reminder_supports_multiple_workshop_services(): void
    {
        Http::fake([
            'hiwa.my.id/*' => Http::response([
                'ok' => true,
                'job_id' => 'reminder-job-2',
                'message' => 'Pesan sudah masuk antrian pengiriman.',
            ], 200),
        ]);

        $oilService = WorkshopService::create([
            'code' => 'JSV-OIL-02',
            'name' => 'Ganti Oli + Filter',
            'price' => 75000,
            'is_active' => true,
        ]);

        $tuneUpService = WorkshopService::create([
            'code' => 'JSV-TUNE-02',
            'name' => 'Tune Up Motor',
            'price' => 120000,
            'is_active' => true,
        ]);

        app(SettingService::class)->setMany([
            'hiwa_enabled' => true,
            'hiwa_token_device' => 'token-abc',
            'oil_change_reminder_enabled' => true,
            'oil_change_reminder_months' => 3,
            'oil_change_workshop_service_ids' => [$oilService->id, $tuneUpService->id],
        ]);

        $customer = Customer::create([
            'code' => 'PLG-REM-02',
            'name' => 'Siti Reminder',
            'phone' => '081211112222',
        ]);

        $transaction = Transaction::create([
            'transaction_no' => 'SRV-TEST-0002',
            'type' => 'service',
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'subtotal_items' => 0,
            'subtotal_services' => 120000,
            'discount' => 0,
            'total' => 120000,
            'technician_commission' => 0,
            'owner_service_share' => 120000,
            'owner_items_share' => 0,
            'owner_total_share' => 120000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);
        $transaction->created_at = Carbon::now()->subMonths(3)->subDay();
        $transaction->updated_at = $transaction->created_at;
        $transaction->saveQuietly();

        TransactionServiceLine::create([
            'transaction_id' => $transaction->id,
            'workshop_service_id' => $tuneUpService->id,
            'service_code' => $tuneUpService->code,
            'service_name' => $tuneUpService->name,
            'quantity' => 1,
            'unit_price' => 120000,
            'subtotal' => 120000,
        ]);

        $result = app(OilChangeReminderService::class)->sendDueReminders();

        $this->assertSame(1, $result['sent']);
    }

    #[Test]
    public function admin_can_save_multiple_reminder_services_from_settings_form(): void
    {
        $serviceA = WorkshopService::create([
            'code' => 'JSV-SET-A',
            'name' => 'Servis A',
            'price' => 50000,
            'is_active' => true,
        ]);

        $serviceB = WorkshopService::create([
            'code' => 'JSV-SET-B',
            'name' => 'Servis B',
            'price' => 60000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'app_name' => 'Atha Motor',
                'oil_change_reminder_months' => 3,
                'oil_change_workshop_service_ids' => [$serviceA->id, $serviceB->id],
            ])
            ->assertRedirect(route('settings.edit'));

        $ids = app(SettingService::class)->getIntArray('oil_change_workshop_service_ids');
        $this->assertSame([$serviceA->id, $serviceB->id], $ids);
    }

    #[Test]
    public function reminder_returns_error_when_disabled(): void
    {
        app(SettingService::class)->setMany([
            'oil_change_reminder_enabled' => false,
            'hiwa_enabled' => true,
            'hiwa_token_device' => 'token',
        ]);

        $result = app(OilChangeReminderService::class)->sendDueReminders();

        $this->assertSame(0, $result['sent']);
        $this->assertNotEmpty($result['errors']);
    }

    #[Test]
    public function reminder_skips_customer_without_phone(): void
    {
        Http::fake(['hiwa.my.id/*' => Http::response(['ok' => true, 'job_id' => 'x'], 200)]);

        $service = WorkshopService::create([
            'code' => 'JSV-NOPHONE',
            'name' => 'Ganti Oli',
            'price' => 50000,
            'is_active' => true,
        ]);

        app(SettingService::class)->setMany([
            'hiwa_enabled' => true,
            'hiwa_token_device' => 'token',
            'oil_change_reminder_enabled' => true,
            'oil_change_reminder_months' => 3,
            'oil_change_workshop_service_ids' => [$service->id],
        ]);

        $customer = Customer::create([
            'code' => 'PLG-NOPHONE',
            'name' => 'Tanpa HP',
            'phone' => null,
        ]);

        $transaction = Transaction::create([
            'transaction_no' => 'SRV-NOPHONE-01',
            'type' => 'service',
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'subtotal_items' => 0,
            'subtotal_services' => 50000,
            'discount' => 0,
            'total' => 50000,
            'technician_commission' => 0,
            'owner_service_share' => 50000,
            'owner_items_share' => 0,
            'owner_total_share' => 50000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);
        $transaction->created_at = Carbon::now()->subMonths(4);
        $transaction->saveQuietly();

        TransactionServiceLine::create([
            'transaction_id' => $transaction->id,
            'workshop_service_id' => $service->id,
            'service_code' => $service->code,
            'service_name' => $service->name,
            'quantity' => 1,
            'unit_price' => 50000,
            'subtotal' => 50000,
        ]);

        $result = app(OilChangeReminderService::class)->sendDueReminders();

        $this->assertSame(0, $result['sent']);
        $this->assertGreaterThan(0, $result['skipped']);
        Http::assertNothingSent();
    }

    #[Test]
    public function reminder_ignores_services_not_in_selected_list(): void
    {
        Http::fake(['hiwa.my.id/*' => Http::response(['ok' => true, 'job_id' => 'x'], 200)]);

        $selected = WorkshopService::create([
            'code' => 'JSV-SEL',
            'name' => 'Ganti Oli',
            'price' => 50000,
            'is_active' => true,
        ]);

        $other = WorkshopService::create([
            'code' => 'JSV-OTHER',
            'name' => 'Servis AC',
            'price' => 80000,
            'is_active' => true,
        ]);

        app(SettingService::class)->setMany([
            'hiwa_enabled' => true,
            'hiwa_token_device' => 'token',
            'oil_change_reminder_enabled' => true,
            'oil_change_reminder_months' => 3,
            'oil_change_workshop_service_ids' => [$selected->id],
        ]);

        $customer = Customer::create([
            'code' => 'PLG-OTHER',
            'name' => 'Cust AC',
            'phone' => '081299988877',
        ]);

        $transaction = Transaction::create([
            'transaction_no' => 'SRV-OTHER-01',
            'type' => 'service',
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'subtotal_items' => 0,
            'subtotal_services' => 80000,
            'discount' => 0,
            'total' => 80000,
            'technician_commission' => 0,
            'owner_service_share' => 80000,
            'owner_items_share' => 0,
            'owner_total_share' => 80000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);
        $transaction->created_at = Carbon::now()->subMonths(4);
        $transaction->saveQuietly();

        TransactionServiceLine::create([
            'transaction_id' => $transaction->id,
            'workshop_service_id' => $other->id,
            'service_code' => $other->code,
            'service_name' => $other->name,
            'quantity' => 1,
            'unit_price' => 80000,
            'subtotal' => 80000,
        ]);

        $result = app(OilChangeReminderService::class)->sendDueReminders();

        $this->assertSame(0, $result['sent']);
        Http::assertNothingSent();
    }

    #[Test]
    public function reminder_skips_when_interval_not_yet_reached(): void
    {
        Http::fake(['hiwa.my.id/*' => Http::response(['ok' => true, 'job_id' => 'x'], 200)]);

        $service = WorkshopService::create([
            'code' => 'JSV-EARLY',
            'name' => 'Ganti Oli',
            'price' => 50000,
            'is_active' => true,
        ]);

        app(SettingService::class)->setMany([
            'hiwa_enabled' => true,
            'hiwa_token_device' => 'token',
            'oil_change_reminder_enabled' => true,
            'oil_change_reminder_months' => 3,
            'oil_change_workshop_service_ids' => [$service->id],
        ]);

        $customer = Customer::create([
            'code' => 'PLG-EARLY',
            'name' => 'Belum Jatuh Tempo',
            'phone' => '081288877766',
        ]);

        $transaction = Transaction::create([
            'transaction_no' => 'SRV-EARLY-01',
            'type' => 'service',
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'subtotal_items' => 0,
            'subtotal_services' => 50000,
            'discount' => 0,
            'total' => 50000,
            'technician_commission' => 0,
            'owner_service_share' => 50000,
            'owner_items_share' => 0,
            'owner_total_share' => 50000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);
        $transaction->created_at = Carbon::now()->subMonth();
        $transaction->saveQuietly();

        TransactionServiceLine::create([
            'transaction_id' => $transaction->id,
            'workshop_service_id' => $service->id,
            'service_code' => $service->code,
            'service_name' => $service->name,
            'quantity' => 1,
            'unit_price' => 50000,
            'subtotal' => 50000,
        ]);

        $result = app(OilChangeReminderService::class)->sendDueReminders();

        $this->assertSame(0, $result['sent']);
        Http::assertNothingSent();
    }

    #[Test]
    public function kasir_cannot_run_reminders_manually(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $this->actingAs($kasir)
            ->postJson(route('settings.run-reminders'))
            ->assertForbidden();
    }
}
