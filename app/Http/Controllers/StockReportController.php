<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class StockReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:stock report view')->only('index');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            $query = Item::query()
                ->with(['category:id,name', 'unit:id,name,abbreviation'])
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('category_name', fn (Item $i) => $i->category?->name ?? '-')
                ->addColumn('unit_name', fn (Item $i) => $i->unit?->abbreviation ?: $i->unit?->name ?: '-')
                ->addColumn('stock_display', function (Item $i) {
                    if ($i->isLowStock()) {
                        return '<span class="badge bg-danger-subtle text-danger">'.number_format($i->stock).'</span>';
                    }

                    return '<span class="text-success fw-semibold">'.number_format($i->stock).'</span>';
                })
                ->addColumn('stock_opname', fn (Item $i) => number_format($i->stock_opname))
                ->addColumn('status', function (Item $i) {
                    if (! $i->is_active) {
                        return '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>';
                    }
                    if ($i->isLowStock()) {
                        return '<span class="badge bg-danger-subtle text-danger">Stok Menipis</span>';
                    }

                    return '<span class="badge bg-success-subtle text-success">Aman</span>';
                })
                ->addColumn('selling_price', fn (Item $i) => 'Rp '.number_format((float) $i->selling_price, 0, ',', '.'))
                ->addColumn('created_at', fn (Item $i) => $i->created_at?->format('Y-m-d H:i:s'))
                ->rawColumns(['stock_display', 'status'])
                ->toJson();
        }

        $stats = [
            'total_items' => Item::count(),
            'low_stock' => Item::lowStock()->count(),
            'out_of_stock' => Item::where('stock', 0)->where('is_active', true)->count(),
            'total_stock_value' => Item::selectRaw('SUM(stock * purchase_price) as total')->value('total') ?? 0,
        ];

        return view('stock-reports.index', compact('stats'));
    }
}
