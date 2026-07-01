<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\ItemCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ItemCategoryController extends Controller
{
    use RespondsToModal;

    public function __construct()
    {
        $this->middleware('permission:item category view')->only(['index', 'show']);
        $this->middleware('permission:item category create')->only('store');
        $this->middleware('permission:item category edit')->only('update');
        $this->middleware('permission:item category delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(ItemCategory::query()->withCount('items')->latest())
                ->addIndexColumn()
                ->addColumn('items_count', fn (ItemCategory $c) => $c->items_count)
                ->addColumn('created_at', fn (ItemCategory $c) => $c->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'item-categories.include.action')
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('item-categories.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:item_categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        $category = ItemCategory::create($validated);

        return $this->modalSuccess('Kategori berhasil ditambahkan.', $category);
    }

    public function show(ItemCategory $itemCategory): JsonResponse
    {
        $itemCategory->loadCount('items');

        return response()->json(['data' => $itemCategory]);
    }

    public function update(Request $request, ItemCategory $itemCategory): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:item_categories,name,'.$itemCategory->id],
            'description' => ['nullable', 'string'],
        ]);

        $itemCategory->update($validated);

        return $this->modalSuccess('Kategori berhasil diperbarui.', $itemCategory);
    }

    public function destroy(ItemCategory $itemCategory): JsonResponse|RedirectResponse
    {
        if ($itemCategory->items()->exists()) {
            return $this->modalError('Kategori masih digunakan oleh barang.');
        }

        $itemCategory->delete();

        return $this->modalSuccess('Kategori berhasil dihapus.');
    }
}
