<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BankAccountController extends Controller
{
    use RespondsToModal;

    public function __construct()
    {
        $this->middleware('permission:bank account view')->only(['index', 'show']);
        $this->middleware('permission:bank account create')->only('store');
        $this->middleware('permission:bank account edit')->only('update');
        $this->middleware('permission:bank account delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(BankAccount::query()->latest())
                ->addIndexColumn()
                ->addColumn('account_display', fn (BankAccount $b) => e($b->displayLabel()))
                ->addColumn('status', fn (BankAccount $b) => $b->is_active
                    ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>')
                ->addColumn('created_at', fn (BankAccount $b) => $b->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'bank-accounts.include.action')
                ->rawColumns(['action', 'status'])
                ->toJson();
        }

        return view('bank-accounts.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $account = BankAccount::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->modalSuccess('Akun bank berhasil ditambahkan.', $account);
    }

    public function show(BankAccount $bankAccount): JsonResponse
    {
        return response()->json(['data' => $bankAccount]);
    }

    public function update(Request $request, BankAccount $bankAccount): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $bankAccount->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->modalSuccess('Akun bank berhasil diperbarui.', $bankAccount);
    }

    public function destroy(BankAccount $bankAccount): JsonResponse|RedirectResponse
    {
        if ($bankAccount->transactions()->exists() || $bankAccount->purchases()->exists()) {
            return $this->modalError('Akun bank sudah dipakai di transaksi dan tidak dapat dihapus.');
        }

        $bankAccount->delete();

        return $this->modalSuccess('Akun bank berhasil dihapus.');
    }
}
