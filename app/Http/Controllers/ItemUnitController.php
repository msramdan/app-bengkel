<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\ItemUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ItemUnitController extends Controller
{
    use RespondsToModal;

    public function __construct()
    {
        $this->middleware('permission:item unit view')->only(['index', 'show']);
        $this->middleware('permission:item unit create')->only('store');
        $this->middleware('permission:item unit edit')->only('update');
        $this->middleware('permission:item unit delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(ItemUnit::query()->withCount('items')->latest())
                ->addIndexColumn()
                ->addColumn('abbreviation', fn (ItemUnit $u) => $u->abbreviation ?: '-')
                ->addColumn('items_count', fn (ItemUnit $u) => $u->items_count)
                ->addColumn('created_at', fn (ItemUnit $u) => $u->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'item-units.include.action')
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('item-units.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:item_units,name'],
            'abbreviation' => ['nullable', 'string', 'max:20'],
        ]);

        $unit = ItemUnit::create($validated);

        return $this->modalSuccess('Satuan berhasil ditambahkan.', $unit);
    }

    public function show(ItemUnit $itemUnit): JsonResponse
    {
        $itemUnit->loadCount('items');

        return response()->json(['data' => $itemUnit]);
    }

    public function update(Request $request, ItemUnit $itemUnit): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:item_units,name,'.$itemUnit->id],
            'abbreviation' => ['nullable', 'string', 'max:20'],
        ]);

        $itemUnit->update($validated);

        return $this->modalSuccess('Satuan berhasil diperbarui.', $itemUnit);
    }

    public function destroy(ItemUnit $itemUnit): JsonResponse|RedirectResponse
    {
        if ($itemUnit->items()->exists()) {
            return $this->modalError('Satuan masih digunakan oleh barang.');
        }

        $itemUnit->delete();

        return $this->modalSuccess('Satuan berhasil dihapus.');
    }
}
