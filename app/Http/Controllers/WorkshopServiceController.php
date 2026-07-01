<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\WorkshopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class WorkshopServiceController extends Controller
{
    use RespondsToModal;

    public function __construct()
    {
        $this->middleware('permission:workshop service view')->only(['index', 'show']);
        $this->middleware('permission:workshop service create')->only('store');
        $this->middleware('permission:workshop service edit')->only('update');
        $this->middleware('permission:workshop service delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(WorkshopService::query()->latest())
                ->addIndexColumn()
                ->addColumn('price_fmt', fn (WorkshopService $s) => 'Rp '.number_format((float) $s->price, 0, ',', '.'))
                ->addColumn('status', fn (WorkshopService $s) => $s->is_active
                    ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>')
                ->addColumn('created_at', fn (WorkshopService $s) => $s->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'workshop-services.include.action')
                ->rawColumns(['action', 'status'])
                ->toJson();
        }

        return view('workshop-services.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service = WorkshopService::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'is_active' => $request->boolean('is_active', true),
            'code' => WorkshopService::generateCode(),
        ]);

        return $this->modalSuccess('Jasa servis berhasil ditambahkan.', $service);
    }

    public function show(WorkshopService $workshopService): JsonResponse
    {
        return response()->json(['data' => $workshopService]);
    }

    public function update(Request $request, WorkshopService $workshopService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $workshopService->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->modalSuccess('Jasa servis berhasil diperbarui.', $workshopService);
    }

    public function destroy(WorkshopService $workshopService): JsonResponse|RedirectResponse
    {
        $workshopService->delete();

        return $this->modalSuccess('Jasa servis berhasil dihapus.');
    }
}
