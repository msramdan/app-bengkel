<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemImportExportController;
use App\Http\Controllers\ItemUnitController;
use App\Http\Controllers\ManualExpenseController;
use App\Http\Controllers\ManualIncomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkshopServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard view');

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);

    // Fase 1 — Master data & inventory
    Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
    Route::resource('technicians', TechnicianController::class)->except(['create', 'edit']);
    Route::resource('item-categories', ItemCategoryController::class)->except(['create', 'edit']);
    Route::resource('item-units', ItemUnitController::class)->except(['create', 'edit']);
    Route::get('items/export', [ItemImportExportController::class, 'export'])->name('items.export');
    Route::get('items/import/template', [ItemImportExportController::class, 'template'])->name('items.import.template');
    Route::post('items/import', [ItemImportExportController::class, 'import'])->name('items.import');
    Route::resource('items', ItemController::class)->except(['create', 'edit']);

    Route::get('stock-ins', [StockInController::class, 'index'])->name('stock-ins.index');
    Route::post('stock-ins', [StockInController::class, 'store'])->name('stock-ins.store');
    Route::get('stock-ins/batch/{batchNo}', [StockInController::class, 'showBatch'])->name('stock-ins.batch');

    Route::get('stock-outs', [StockOutController::class, 'index'])->name('stock-outs.index');
    Route::post('stock-outs', [StockOutController::class, 'store'])->name('stock-outs.store');
    Route::get('stock-outs/batch/{batchNo}', [StockOutController::class, 'showBatch'])->name('stock-outs.batch');

    Route::get('stock-reports', [StockReportController::class, 'index'])->name('stock-reports.index');

    // Fase 2 — Transaksi & komisi teknisi
    Route::resource('workshop-services', WorkshopServiceController::class)->except(['create', 'edit']);
    Route::get('transactions/items/availability', [TransactionController::class, 'itemAvailability'])->name('transactions.items.availability');
    Route::get('transactions/export-pdf', [TransactionController::class, 'exportPdf'])->name('transactions.export-pdf');
    Route::get('transactions/{transaction}/invoice', [TransactionController::class, 'invoice'])->name('transactions.invoice');
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::get('purchases/export-pdf', [PurchaseController::class, 'exportPdf'])->name('purchases.export-pdf');
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::get('financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index');
    Route::get('financial-reports/export-pdf', [FinancialReportController::class, 'exportPdf'])->name('financial-reports.export-pdf');
    Route::get('financial-reports/technicians/{technician}/commissions', [FinancialReportController::class, 'technicianCommissions'])->name('financial-reports.technician-commissions');
    Route::get('financial-reports/technicians/{technician}/commissions/pdf', [FinancialReportController::class, 'exportTechnicianCommissionsPdf'])->name('financial-reports.technician-commissions-pdf');

    Route::get('manual-incomes', [ManualIncomeController::class, 'index'])->name('manual-incomes.index');
    Route::post('manual-incomes', [ManualIncomeController::class, 'store'])->name('manual-incomes.store');
    Route::get('manual-incomes/{manualIncome}', [ManualIncomeController::class, 'show'])->name('manual-incomes.show');
    Route::delete('manual-incomes/{manualIncome}', [ManualIncomeController::class, 'destroy'])->name('manual-incomes.destroy');

    Route::get('manual-expenses', [ManualExpenseController::class, 'index'])->name('manual-expenses.index');
    Route::post('manual-expenses', [ManualExpenseController::class, 'store'])->name('manual-expenses.store');
    Route::get('manual-expenses/{manualExpense}', [ManualExpenseController::class, 'show'])->name('manual-expenses.show');
    Route::delete('manual-expenses/{manualExpense}', [ManualExpenseController::class, 'destroy'])->name('manual-expenses.destroy');

    Route::resource('bank-accounts', BankAccountController::class)->except(['create', 'edit']);

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/run-reminders', [SettingController::class, 'runReminders'])->name('settings.run-reminders');
});
