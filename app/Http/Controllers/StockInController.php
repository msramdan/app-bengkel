<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use App\Support\StockReferenceValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;
use Yajra\DataTables\Facades\DataTables;

class StockInController extends Controller
{
    use RespondsToModal;

    public function __construct(private StockService $stockService)
    {
        $this->middleware('permission:stock in view')->only(['index', 'showBatch']);
        $this->middleware('permission:stock in create')->only('store');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            $query = StockMovement::query()
                ->where('type', 'in')
                ->select([
                    DB::raw('COALESCE(batch_no, CONCAT("legacy-", id)) as batch_key'),
                    'batch_no',
                    DB::raw('MIN(id) as id'),
                    DB::raw('COUNT(*) as item_count'),
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('MAX(reference_no) as reference_no'),
                    DB::raw('MAX(user_id) as user_id'),
                    DB::raw('MIN(created_at) as created_at'),
                ])
                ->groupBy(DB::raw('COALESCE(batch_no, CONCAT("legacy-", id))'), 'batch_no')
                ->orderByDesc(DB::raw('MIN(created_at)'));

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('batch_no_display', fn ($row) => $row->batch_no ?? '-')
                ->addColumn('items_label', function ($row) {
                    if ($row->item_count > 1) {
                        return '<span class="fw-medium">'.$row->item_count.' barang</span>';
                    }

                    $movement = StockMovement::with('item:id,code,name')->find($row->id);

                    return $movement?->item
                        ? e($movement->item->code.' — '.$movement->item->name)
                        : '-';
                })
                ->addColumn('quantity', fn ($row) => '+'.number_format((int) $row->total_quantity))
                ->addColumn('source_label', fn ($row) => $this->stockInSourceLabel($row->reference_no))
                ->addColumn('stock_change', fn ($row) => $row->item_count > 1
                    ? '<span class="text-muted">Multi barang</span>'
                    : $this->singleStockChange($row->id))
                ->addColumn('user_name', fn ($row) => User::find($row->user_id)?->name ?? '-')
                ->addColumn('created_at', fn ($row) => $row->created_at
                    ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i')
                    : '-')
                ->addColumn('action', 'stock-ins.include.action')
                ->rawColumns(['action', 'items_label', 'stock_change', 'source_label'])
                ->toJson();
        }

        return view('stock-ins.index', [
            'items' => Item::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'stock']),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reference_no' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            StockReferenceValidator::assertManualReference($validated['reference_no'] ?? null);

            $result = $this->stockService->stockInBatch(
                $validated['items'],
                (int) auth()->id(),
                $validated['reference_no'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return $this->modalError($e->getMessage());
        }

        $count = $result['movements']->count();

        return $this->modalSuccess(
            "Stok masuk berhasil dicatat ({$count} barang).",
            ['batch_no' => $result['batch_no'], 'item_count' => $count]
        );
    }

    public function showBatch(string $batchNo): JsonResponse
    {
        $movements = StockMovement::query()
            ->where('type', 'in')
            ->where('batch_no', $batchNo)
            ->with(['item.category', 'item.unit', 'user:id,name'])
            ->orderBy('id')
            ->get();

        abort_if($movements->isEmpty(), 404);

        $first = $movements->first();

        return response()->json([
            'data' => [
                'batch_no' => $batchNo,
                'reference_no' => $first->reference_no,
                'notes' => $first->notes,
                'user' => $first->user,
                'created_at' => $first->created_at,
                'items' => $movements->map(fn (StockMovement $m) => [
                    'code' => $m->item?->code,
                    'name' => $m->item?->name,
                    'quantity' => $m->quantity,
                    'stock_before' => $m->stock_before,
                    'stock_after' => $m->stock_after,
                    'unit' => $m->item?->unit?->abbreviation ?: $m->item?->unit?->name,
                ]),
            ],
        ]);
    }

    private function singleStockChange(int $movementId): string
    {
        $movement = StockMovement::find($movementId);

        if (! $movement) {
            return '-';
        }

        return number_format($movement->stock_before).' → '.number_format($movement->stock_after);
    }

    private function stockInSourceLabel(?string $referenceNo): string
    {
        if (! $referenceNo) {
            return '<span class="badge bg-secondary-subtle text-secondary">Manual</span>';
        }

        if (preg_match('/^PBL-/', $referenceNo)) {
            return '<span class="badge bg-warning-subtle text-warning" title="'.e($referenceNo).'">Pembelian Barang</span>';
        }

        return '<span class="text-muted small">'.e($referenceNo).'</span>';
    }
}
