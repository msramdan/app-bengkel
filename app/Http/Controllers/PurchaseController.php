<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\Item;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Yajra\DataTables\Facades\DataTables;

class PurchaseController extends Controller
{
    use RespondsToModal;

    public function __construct(private PurchaseService $purchaseService)
    {
        $this->middleware('permission:purchase view')->only(['index', 'create', 'show']);
        $this->middleware('permission:purchase create')->only('store');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(Purchase::query()->with(['user:id,name', 'bankAccount'])->latest())
                ->addIndexColumn()
                ->addColumn('supplier_name', fn (Purchase $p) => e($p->supplier_name ?: '-'))
                ->addColumn('total_fmt', fn (Purchase $p) => 'Rp '.number_format((float) $p->total, 0, ',', '.'))
                ->addColumn('payment_label', fn (Purchase $p) => $this->paymentBadge($p))
                ->addColumn('user_name', fn (Purchase $p) => e($p->user?->name ?? '-'))
                ->addColumn('created_at', fn (Purchase $p) => $p->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'purchases.include.action')
                ->rawColumns(['action', 'payment_label'])
                ->toJson();
        }

        return view('purchases.index');
    }

    public function create(): View
    {
        return view('purchases.create', [
            'items' => Item::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'stock', 'purchase_price']),
            'bankAccounts' => \App\Models\BankAccount::query()->where('is_active', true)->orderBy('bank_name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
        ]);

        try {
            $purchase = $this->purchaseService->create($validated, (int) auth()->id());
        } catch (InvalidArgumentException $e) {
            return $this->modalError($e->getMessage());
        }

        if ($request->expectsJson()) {
            return $this->modalSuccess(
                'Pembelian berhasil disimpan. Stok masuk & pengeluaran tercatat.',
                ['purchase_no' => $purchase->purchase_no, 'id' => $purchase->id]
            );
        }

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Pembelian '.$purchase->purchase_no.' berhasil disimpan.');
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $purchase->load(['user:id,name', 'items', 'bankAccount']);

        return response()->json(['data' => $purchase]);
    }

    private function paymentBadge(Purchase $p): string
    {
        $label = \App\Support\PaymentMethodResolver::label($p->payment_method);

        if ($p->payment_method === 'transfer' && $p->bankAccount) {
            return '<span class="badge bg-warning-subtle text-warning" title="'.e($p->bankAccount->displayLabel()).'">'.e($label).'</span>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary">'.e($label).'</span>';
    }
}
