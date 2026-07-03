<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    use RespondsToModal;

    public function __construct()
    {
        $this->middleware('permission:supplier view')->only(['index', 'show']);
        $this->middleware('permission:supplier create')->only('store');
        $this->middleware('permission:supplier edit')->only('update');
        $this->middleware('permission:supplier delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(Supplier::query()->latest())
                ->addIndexColumn()
                ->addColumn('phone', fn (Supplier $s) => $s->phone ?: '-')
                ->addColumn('created_at', fn (Supplier $s) => $s->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'suppliers.include.action')
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('suppliers.index');
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

        $supplier = Supplier::create([
            ...$validated,
            'code' => Supplier::generateCode(),
        ]);

        return $this->modalSuccess('Supplier berhasil ditambahkan.', $supplier);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json(['data' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return $this->modalSuccess('Supplier berhasil diperbarui.', $supplier);
    }

    public function destroy(Supplier $supplier): JsonResponse|RedirectResponse
    {
        $supplier->delete();

        return $this->modalSuccess('Supplier berhasil dihapus.');
    }
}
