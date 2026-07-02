<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\BankAccount;
use App\Models\FinancialCategory;
use App\Models\ManualCashEntry;
use App\Services\ManualCashEntryService;
use App\Support\PaymentMethodResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Yajra\DataTables\Facades\DataTables;

class ManualExpenseController extends Controller
{
    use RespondsToModal;

    public function __construct(private ManualCashEntryService $service)
    {
        $this->middleware('permission:manual expense view')->only(['index', 'show']);
        $this->middleware('permission:manual expense create')->only('store');
        $this->middleware('permission:manual expense cancel')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return $this->datatable('expense');
        }

        return view('manual-cash-entries.expense-index', [
            'categories' => FinancialCategory::expense()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'bankAccounts' => BankAccount::query()->where('is_active', true)->orderBy('bank_name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        try {
            $entry = $this->service->create($validated, 'expense', (int) $request->user()->id);

            return $this->modalSuccess('Pengeluaran manual berhasil dicatat.', $entry->load(['category', 'user', 'bankAccount']));
        } catch (InvalidArgumentException $e) {
            return $this->modalError($e->getMessage());
        }
    }

    public function show(ManualCashEntry $manualExpense): JsonResponse
    {
        abort_unless($manualExpense->type === 'expense', 404);

        $manualExpense->load(['category', 'user', 'bankAccount', 'cancelledByUser']);

        return response()->json(['data' => $manualExpense]);
    }

    public function destroy(Request $request, ManualCashEntry $manualExpense): JsonResponse
    {
        abort_unless($manualExpense->type === 'expense', 404);

        try {
            $entry = $this->service->cancel($manualExpense, (int) $request->user()->id);

            return $this->modalSuccess('Pengeluaran manual berhasil dibatalkan.', $entry);
        } catch (InvalidArgumentException $e) {
            return $this->modalError($e->getMessage());
        }
    }

    private function datatable(string $type): JsonResponse
    {
        [$from, $to] = $this->resolvePeriod();
        $categoryId = request()->integer('category_id');

        $query = ManualCashEntry::query()
            ->with(['category:id,name', 'user:id,name', 'bankAccount:id,bank_name,account_number'])
            ->where('type', $type)
            ->whereBetween('occurred_at', [$from, $to])
            ->when($categoryId > 0, fn ($q) => $q->where('category_id', $categoryId))
            ->latest('occurred_at');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('amount_fmt', fn (ManualCashEntry $e) => 'Rp '.number_format((float) $e->amount, 0, ',', '.'))
            ->addColumn('occurred_at_fmt', fn (ManualCashEntry $e) => $e->occurred_at?->format('d/m/Y H:i'))
            ->addColumn('category_name', fn (ManualCashEntry $e) => e($e->category?->name ?? '-'))
            ->addColumn('payment_label', fn (ManualCashEntry $e) => $this->paymentLabel($e))
            ->addColumn('status_label', fn (ManualCashEntry $e) => $e->isCompleted()
                ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Batal</span>')
            ->addColumn('description_short', fn (ManualCashEntry $e) => e(str($e->description ?? '—')->limit(60)))
            ->addColumn('action', 'manual-cash-entries.include.expense-action')
            ->rawColumns(['action', 'status_label'])
            ->toJson();
    }

    private function paymentLabel(ManualCashEntry $entry): string
    {
        $label = PaymentMethodResolver::label($entry->payment_method);

        if ($entry->payment_method === 'transfer' && $entry->bankAccount) {
            return e($label.' — '.$entry->bankAccount->bank_name);
        }

        return e($label);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(): array
    {
        $from = request()->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = request()->date('to')?->endOfDay() ?? now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $methods = PaymentMethodResolver::purchaseMethods();

        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:financial_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_at' => ['required', 'date'],
            'payment_method' => ['required', 'in:'.implode(',', $methods)],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
