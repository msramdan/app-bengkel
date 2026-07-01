<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesEntityPhoto;
use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ItemController extends Controller
{
    use HandlesEntityPhoto, RespondsToModal;

    public function __construct()
    {
        $this->middleware('permission:item view')->only(['index', 'show']);
        $this->middleware('permission:item create')->only('store');
        $this->middleware('permission:item edit')->only('update');
        $this->middleware('permission:item delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            $query = Item::query()
                ->with(['category:id,name', 'unit:id,name,abbreviation'])
                ->latest();

            $this->applyListFilters($query);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('photo_thumb', function (Item $i) {
                    if ($i->photo_url) {
                        return '<img src="'.e($i->photo_url).'" alt="" class="entity-thumb">';
                    }

                    return '<span class="entity-thumb entity-thumb-empty"><i class="bi bi-box-seam"></i></span>';
                })
                ->addColumn('category_name', fn (Item $i) => $i->category?->name ?? '-')
                ->addColumn('unit_name', fn (Item $i) => $i->unit?->abbreviation ?: $i->unit?->name ?: '-')
                ->addColumn('stock_display', function (Item $i) {
                    $class = $i->isLowStock() ? 'text-danger fw-semibold' : '';

                    return '<span class="'.$class.'">'.number_format($i->stock).'</span>';
                })
                ->addColumn('stock_opname_display', function (Item $i) {
                    if ((int) $i->stock_opname <= 0) {
                        return '<span class="text-muted">—</span>';
                    }

                    return '<span class="text-center d-inline-block">'.number_format($i->stock_opname).'</span>';
                })
                ->addColumn('selling_price', fn (Item $i) => 'Rp '.number_format((float) $i->selling_price, 0, ',', '.'))
                ->addColumn('member_price', fn (Item $i) => 'Rp '.number_format((float) $i->member_price, 0, ',', '.'))
                ->addColumn('status', fn (Item $i) => $i->is_active
                    ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>')
                ->addColumn('created_at', fn (Item $i) => $i->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'items.include.action')
                ->rawColumns(['action', 'status', 'stock_display', 'stock_opname_display', 'photo_thumb'])
                ->toJson();
        }

        return view('items.index', [
            'categories' => ItemCategory::orderBy('name')->get(['id', 'name']),
            'units' => ItemUnit::orderBy('name')->get(['id', 'name', 'abbreviation']),
            'filters' => [
                'category_id' => request()->integer('category_id') ?: '',
                'unit_id' => request()->integer('unit_id') ?: '',
                'low_stock' => request()->boolean('low_stock'),
            ],
        ]);
    }

    private function applyListFilters($query): void
    {
        $categoryId = request()->integer('category_id');
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        $unitId = request()->integer('unit_id');
        if ($unitId > 0) {
            $query->where('unit_id', $unitId);
        }

        if (request()->boolean('low_stock')) {
            $query->lowStock();
        }
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:item_categories,id'],
            'unit_id' => ['required', 'exists:item_units,id'],
            'stock_opname' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'member_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], $this->photoRules()));

        $item = Item::create([
            ...collect($validated)->except(['photo', 'remove_photo'])->all(),
            'code' => Item::generateCode(),
            'photo' => $this->resolvePhotoPath($request, 'photos/items'),
            'stock' => 0,
            'stock_opname' => $validated['stock_opname'] ?? 0,
            'purchase_price' => $validated['purchase_price'] ?? 0,
            'selling_price' => $validated['selling_price'] ?? 0,
            'member_price' => $validated['member_price'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $item->load(['category:id,name', 'unit:id,name,abbreviation']);

        return $this->modalSuccess('Barang berhasil ditambahkan.', $item);
    }

    public function show(Item $item): JsonResponse
    {
        $item->load(['category:id,name', 'unit:id,name,abbreviation']);

        return response()->json(['data' => $item]);
    }

    public function update(Request $request, Item $item): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:item_categories,id'],
            'unit_id' => ['required', 'exists:item_units,id'],
            'stock_opname' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'member_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], $this->photoRules()));

        $item->update([
            ...collect($validated)->except(['photo', 'remove_photo'])->all(),
            'photo' => $this->resolvePhotoPath($request, 'photos/items', $item->photo),
            'stock_opname' => $validated['stock_opname'] ?? 0,
            'purchase_price' => $validated['purchase_price'] ?? 0,
            'selling_price' => $validated['selling_price'] ?? 0,
            'member_price' => $validated['member_price'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        $item->load(['category:id,name', 'unit:id,name,abbreviation']);

        return $this->modalSuccess('Barang berhasil diperbarui.', $item->fresh());
    }

    public function destroy(Item $item): JsonResponse|RedirectResponse
    {
        if ($item->stock > 0) {
            return $this->modalError('Barang masih memiliki stok. Kosongkan stok terlebih dahulu.');
        }

        if ($item->stockMovements()->exists()) {
            return $this->modalError('Barang memiliki riwayat stok dan tidak dapat dihapus.');
        }

        $item->deletePhotoFile();
        $item->delete();

        return $this->modalSuccess('Barang berhasil dihapus.');
    }
}
