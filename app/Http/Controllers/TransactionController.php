<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Technician;
use App\Models\Transaction;
use App\Models\WorkshopService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Yajra\DataTables\Facades\DataTables;

class TransactionController extends Controller
{
    use RespondsToModal;

    public function __construct(private TransactionService $transactionService)
    {
        $this->middleware('permission:transaction view')->only(['index', 'show', 'invoice']);
        $this->middleware('permission:transaction create')->only(['create', 'store']);
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(
                Transaction::query()
                    ->with(['customer:id,code,name', 'technician:id,code,name', 'user:id,name', 'bankAccount'])
                    ->latest()
            )
                ->addIndexColumn()
                ->addColumn('type_label', fn (Transaction $t) => match ($t->type) {
                    'sale' => '<span class="badge bg-primary-subtle text-primary">Penjualan</span>',
                    'service' => '<span class="badge bg-info-subtle text-info">Servis</span>',
                    'combined' => '<span class="badge bg-warning-subtle text-warning">Gabungan</span>',
                    default => e($t->type),
                })
                ->addColumn('customer_name', fn (Transaction $t) => e($t->displayCustomerName()))
                ->addColumn('technician_name', fn (Transaction $t) => e($t->technician?->name ?? '-'))
                ->addColumn('total_fmt', fn (Transaction $t) => 'Rp '.number_format((float) $t->total, 0, ',', '.'))
                ->addColumn('payment_label', fn (Transaction $t) => $this->paymentBadge($t))
                ->addColumn('commission_fmt', fn (Transaction $t) => $t->technician_commission > 0
                    ? 'Rp '.number_format((float) $t->technician_commission, 0, ',', '.')
                    : '-')
                ->addColumn('user_name', fn (Transaction $t) => e($t->user?->name ?? '-'))
                ->addColumn('created_at', fn (Transaction $t) => $t->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'transactions.include.action')
                ->rawColumns(['action', 'type_label', 'payment_label'])
                ->toJson();
        }

        return view('transactions.index');
    }

    public function create(): View
    {
        return view('transactions.create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'code', 'name', 'phone']),
            'technicians' => Technician::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'commission_percent']),
            'items' => Item::query()
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'stock', 'selling_price']),
            'services' => WorkshopService::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'price']),
            'techPercent' => (int) config('workshop.default_technician_commission_percent', 20),
            'ownerPercent' => 100 - (int) config('workshop.default_technician_commission_percent', 20),
            'bankAccounts' => \App\Models\BankAccount::query()->where('is_active', true)->orderBy('bank_name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'customer_mode' => ['required', 'in:existing,umum,new'],
            'customer_id' => ['nullable', 'required_if:customer_mode,existing', 'exists:customers,id'],
            'new_customer' => ['nullable', 'required_if:customer_mode,new', 'array'],
            'new_customer.name' => ['required_if:customer_mode,new', 'string', 'max:255'],
            'new_customer.phone' => ['nullable', 'string', 'max:30'],
            'new_customer.address' => ['nullable', 'string', 'max:500'],
            'technician_id' => ['nullable', 'required_with:services', 'exists:technicians,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['required_with:items', 'exists:items,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'services' => ['nullable', 'array'],
            'services.*.workshop_service_id' => ['required_with:services', 'exists:workshop_services,id'],
            'services.*.quantity' => ['required_with:services', 'integer', 'min:1'],
            'services.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'bank_account_id' => ['nullable', 'required_if:payment_method,transfer', 'exists:bank_accounts,id'],
        ]);

        try {
            $transaction = $this->transactionService->create($validated, (int) auth()->id());
        } catch (InvalidArgumentException $e) {
            return $this->modalError($e->getMessage());
        }

        if ($request->expectsJson()) {
            return $this->modalSuccess(
                'Transaksi berhasil disimpan.',
                ['transaction_no' => $transaction->transaction_no, 'id' => $transaction->id]
            );
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi '.$transaction->transaction_no.' berhasil disimpan.');
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load([
            'customer',
            'technician',
            'user:id,name',
            'items',
            'serviceLines',
            'bankAccount',
        ]);

        return response()->json(['data' => $transaction]);
    }

    public function invoice(Transaction $transaction): View
    {
        $transaction->load([
            'customer',
            'technician',
            'user:id,name',
            'items',
            'serviceLines',
            'bankAccount',
        ]);

        return view('transactions.invoice', compact('transaction'));
    }

    private function paymentBadge(Transaction $t): string
    {
        $label = \App\Support\PaymentMethodResolver::label($t->payment_method);

        if ($t->payment_method === 'transfer' && $t->bankAccount) {
            return '<span class="badge bg-info-subtle text-info" title="'.e($t->bankAccount->displayLabel()).'">'.e($label).'</span>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary">'.e($label).'</span>';
    }
}
