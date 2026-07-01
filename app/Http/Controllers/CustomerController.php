<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    use RespondsToModal;

    public function __construct()
    {
        $this->middleware('permission:customer view')->only(['index', 'show']);
        $this->middleware('permission:customer create')->only('store');
        $this->middleware('permission:customer edit')->only('update');
        $this->middleware('permission:customer delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(Customer::query()->latest())
                ->addIndexColumn()
                ->addColumn('phone', fn (Customer $c) => $c->phone ?: '-')
                ->addColumn('created_at', fn (Customer $c) => $c->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'customers.include.action')
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('customers.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = Customer::create([
            ...$validated,
            'code' => Customer::generateCode(),
        ]);

        return $this->modalSuccess('Pelanggan berhasil ditambahkan.', $customer);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json(['data' => $customer]);
    }

    public function update(Request $request, Customer $customer): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return $this->modalSuccess('Pelanggan berhasil diperbarui.', $customer);
    }

    public function destroy(Customer $customer): JsonResponse|RedirectResponse
    {
        $customer->delete();

        return $this->modalSuccess('Pelanggan berhasil dihapus.');
    }
}
