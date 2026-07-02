<?php

namespace Tests\Feature;

use App\Models\FinancialCategory;
use App\Models\ManualCashEntry;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Services\ManualCashEntryService;
use Carbon\Carbon;
use Database\Seeders\FinancialCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManualCashEntryTest extends TestCase
{
    private User $admin;

    private FinancialCategory $incomeCategory;

    private FinancialCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(FinancialCategorySeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->incomeCategory = FinancialCategory::income()->active()->firstOrFail();
        $this->expenseCategory = FinancialCategory::expense()->active()->firstOrFail();
    }

    #[Test]
    public function admin_can_record_manual_income(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('manual-incomes.store'), [
            'category_id' => $this->incomeCategory->id,
            'amount' => 500000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'payment_method' => 'cash',
            'description' => 'Penjualan besi bekas',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Pemasukan manual berhasil dicatat.');

        $this->assertDatabaseHas('manual_cash_entries', [
            'type' => 'income',
            'category_id' => $this->incomeCategory->id,
            'amount' => 500000,
            'status' => 'completed',
        ]);

        $entry = ManualCashEntry::query()->first();
        $this->assertStringStartsWith('MIN-', $entry->entry_no);
    }

    #[Test]
    public function admin_can_record_manual_expense(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('manual-expenses.store'), [
            'category_id' => $this->expenseCategory->id,
            'amount' => 250000,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'payment_method' => 'cash',
            'description' => 'Gaji karyawan bulan ini',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('manual_cash_entries', [
            'type' => 'expense',
            'category_id' => $this->expenseCategory->id,
            'amount' => 250000,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function cancelled_manual_entries_are_excluded_from_financial_report(): void
    {
        $service = app(ManualCashEntryService::class);

        $income = $service->create([
            'category_id' => $this->incomeCategory->id,
            'amount' => 100000,
            'occurred_at' => now()->toDateTimeString(),
            'payment_method' => 'cash',
            'description' => 'Scrap besi',
        ], 'income', $this->admin->id);

        $expense = $service->create([
            'category_id' => $this->expenseCategory->id,
            'amount' => 50000,
            'occurred_at' => now()->toDateTimeString(),
            'payment_method' => 'cash',
            'description' => 'Listrik',
        ], 'expense', $this->admin->id);

        $service->cancel($income, $this->admin->id);
        $service->cancel($expense, $this->admin->id);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $this->assertSame(0.0, $report['manual_income']['amount']);
        $this->assertSame(0.0, $report['manual_expense']['amount']);
        $this->assertSame(0, $report['manual_income']['entry_count']);
        $this->assertSame(0, $report['manual_expense']['entry_count']);
    }

    #[Test]
    public function financial_report_includes_manual_cash_in_totals(): void
    {
        app(ManualCashEntryService::class)->create([
            'category_id' => $this->incomeCategory->id,
            'amount' => 200000,
            'occurred_at' => now()->toDateTimeString(),
            'payment_method' => 'cash',
        ], 'income', $this->admin->id);

        app(ManualCashEntryService::class)->create([
            'category_id' => $this->expenseCategory->id,
            'amount' => 75000,
            'occurred_at' => now()->toDateTimeString(),
            'payment_method' => 'cash',
        ], 'expense', $this->admin->id);

        $report = app(FinancialReportService::class)->build(Carbon::today(), Carbon::today());

        $this->assertSame(200000.0, $report['manual_income']['amount']);
        $this->assertSame(75000.0, $report['manual_expense']['amount']);
        $this->assertSame(200000.0, $report['totals']['inflow']);
        $this->assertSame(75000.0, $report['totals']['operating_outflow']);
        $this->assertSame(125000.0, $report['profit']['cash_flow_estimate']);
        $this->assertCount(1, $report['manual_income']['by_category']);
        $this->assertCount(1, $report['manual_expense']['by_category']);
    }

    #[Test]
    public function cannot_cancel_entry_twice(): void
    {
        $entry = app(ManualCashEntryService::class)->create([
            'category_id' => $this->incomeCategory->id,
            'amount' => 10000,
            'occurred_at' => now()->toDateTimeString(),
            'payment_method' => 'cash',
        ], 'income', $this->admin->id);

        app(ManualCashEntryService::class)->cancel($entry, $this->admin->id);

        $this->expectException(InvalidArgumentException::class);
        app(ManualCashEntryService::class)->cancel($entry->fresh(), $this->admin->id);
    }

    #[Test]
    public function teknisi_cannot_access_manual_income_pages(): void
    {
        $teknisi = User::factory()->create();
        $teknisi->assignRole('Teknisi');

        $this->actingAs($teknisi)
            ->get(route('manual-incomes.index'))
            ->assertForbidden();

        $this->actingAs($teknisi)
            ->get(route('manual-expenses.index'))
            ->assertForbidden();
    }

    #[Test]
    public function kasir_can_create_manual_income_and_expense(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $this->actingAs($kasir)
            ->get(route('manual-incomes.index'))
            ->assertOk();

        $this->actingAs($kasir)
            ->postJson(route('manual-incomes.store'), [
                'category_id' => $this->incomeCategory->id,
                'amount' => 15000,
                'occurred_at' => now()->format('Y-m-d H:i:s'),
                'payment_method' => 'cash',
            ])
            ->assertOk();

        $this->actingAs($kasir)
            ->postJson(route('manual-expenses.store'), [
                'category_id' => $this->expenseCategory->id,
                'amount' => 10000,
                'occurred_at' => now()->format('Y-m-d H:i:s'),
                'payment_method' => 'cash',
            ])
            ->assertOk();
    }
}
