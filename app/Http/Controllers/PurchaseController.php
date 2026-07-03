<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
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
        $this->middleware('permission:purchase view')->only(['index', 'create', 'show', 'edit']);
        $this->middleware('permission:purchase create')->only('store');
        $this->middleware('permission:purchase edit')->only('update');
        $this->middleware('permission:purchase delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(Purchase::query()->with(['user:id,name', 'bankAccount', 'supplier:id,code,name'])->latest())
                ->addIndexColumn()
                ->addColumn('status_label', fn (Purchase $p) => match ($p->status) {
                    'cancelled' => '<span class="badge bg-danger-subtle text-danger">Batal</span>',
                    default => '<span class="badge bg-success-subtle text-success">Selesai</span>',
                })
                ->addColumn('supplier_name', fn (Purchase $p) => e($p->displaySupplierName()))
                ->addColumn('total_fmt', fn (Purchase $p) => 'Rp '.number_format((float) $p->total, 0, ',', '.'))
                ->addColumn('payment_label', fn (Purchase $p) => $this->paymentBadge($p))
                ->addColumn('user_name', fn (Purchase $p) => e($p->user?->name ?? '-'))
                ->addColumn('created_at', fn (Purchase $p) => $p->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'purchases.include.action')
                ->rawColumns(['action', 'payment_label', 'status_label'])
                ->toJson();
        }

        return view('purchases.index');
    }

    public function create(): View
    {
        return view('purchases.create', [
            'items' => $this->activeItems(),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'code', 'name']),
            'bankAccounts' => \App\Models\BankAccount::query()->where('is_active', true)->orderBy('bank_name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validatePurchasePayload($request);

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
        $purchase->load(['user:id,name', 'items', 'bankAccount', 'supplier:id,code,name', 'cancelledByUser:id,name']);

        return response()->json(['data' => $purchase]);
    }

    public function edit(Purchase $purchase): View
    {
        abort_unless($purchase->isCompleted(), 404);

        $purchase->load(['items', 'supplier', 'bankAccount']);

        return view('purchases.edit', [
            'purchase' => $purchase,
            'items' => $this->activeItems(),
            'bankAccounts' => \App\Models\BankAccount::query()->where('is_active', true)->orderBy('bank_name')->get(),
            'stockCredit' => $purchase->items
                ->mapWithKeys(fn ($line) => [(int) $line->item_id => (int) $line->quantity])
                ->all(),
            'initialItems' => $purchase->items->map(fn ($line) => [
                'item_id' => $line->item_id,
                'code' => $line->item_code,
                'name' => $line->item_name,
                'quantity' => (int) $line->quantity,
                'unit_price' => (float) $line->unit_price,
            ])->values(),
        ]);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse|RedirectResponse
    {
        abort_unless($purchase->isCompleted(), 404);

        $validated = $this->validatePurchasePayload($request, forUpdate: true);

        try {
            $purchase = $this->purchaseService->update($purchase, $validated, (int) auth()->id());
        } catch (InvalidArgumentException $e) {
            return $this->modalError($e->getMessage());
        }

        if ($request->expectsJson()) {
            return $this->modalSuccess(
                'Pembelian berhasil diperbarui. Stok & laporan keuangan disesuaikan.',
                ['purchase_no' => $purchase->purchase_no, 'id' => $purchase->id]
            );
        }

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Pembelian '.$purchase->purchase_no.' berhasil diperbarui.');
    }

    public function destroy(Purchase $purchase): JsonResponse|RedirectResponse
    {
        try {
            $purchase = $this->purchaseService->cancel($purchase, (int) auth()->id());
        } catch (InvalidArgumentException $e) {
            return $this->modalError($e->getMessage());
        }

        if (request()->expectsJson()) {
            return $this->modalSuccess(
                'Pembelian dibatalkan. Stok dikembalikan & pengeluaran dikecualikan dari laporan.',
                ['purchase_no' => $purchase->purchase_no]
            );
        }

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Pembelian '.$purchase->purchase_no.' berhasil dibatalkan.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Item>
     */
    private function activeItems()
    {
        return Item::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'stock', 'purchase_price']);
    }

    private function validatePurchasePayload(Request $request, bool $forUpdate = false): array
    {
        $rules = [
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'bank_account_id' => ['nullable', 'required_if:payment_method,transfer', 'exists:bank_accounts,id'],
        ];

        if (! $forUpdate) {
            $rules = array_merge($rules, [
                'supplier_mode' => ['nullable', 'in:none,existing,new'],
                'supplier_id' => ['nullable', 'required_if:supplier_mode,existing', 'exists:suppliers,id'],
                'new_supplier' => ['nullable', 'array'],
                'new_supplier.name' => ['required_if:supplier_mode,new', 'string', 'max:255'],
                'new_supplier.phone' => ['nullable', 'string', 'max:30'],
                'new_supplier.email' => ['nullable', 'email', 'max:255'],
                'new_supplier.address' => ['nullable', 'string'],
            ]);
        }

        return $request->validate($rules);
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
