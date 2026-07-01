<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesEntityPhoto;
use App\Http\Controllers\Concerns\RespondsToModal;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class TechnicianController extends Controller
{
    use HandlesEntityPhoto, RespondsToModal;

    public function __construct()
    {
        $this->middleware('permission:technician view')->only(['index', 'show']);
        $this->middleware('permission:technician create')->only('store');
        $this->middleware('permission:technician edit')->only('update');
        $this->middleware('permission:technician delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(Technician::query()->latest())
                ->addIndexColumn()
                ->addColumn('photo_thumb', function (Technician $t) {
                    if ($t->photo_url) {
                        return '<img src="'.e($t->photo_url).'" alt="" class="entity-thumb">';
                    }

                    return '<span class="entity-thumb entity-thumb-empty"><i class="bi bi-person"></i></span>';
                })
                ->addColumn('phone', fn (Technician $t) => $t->phone ?: '-')
                ->addColumn('commission_percent', fn (Technician $t) => number_format((float) $t->commission_percent, 0).'%')
                ->addColumn('status', fn (Technician $t) => $t->is_active
                    ? '<span class="badge bg-success-subtle text-success">Aktif</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>')
                ->addColumn('created_at', fn (Technician $t) => $t->created_at?->format('d/m/Y H:i'))
                ->addColumn('action', 'technicians.include.action')
                ->rawColumns(['action', 'status', 'photo_thumb'])
                ->toJson();
        }

        return view('technicians.index');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ], $this->photoRules()));

        $technician = Technician::create([
            ...collect($validated)->except(['photo', 'remove_photo'])->all(),
            'code' => Technician::generateCode(),
            'photo' => $this->resolvePhotoPath($request, 'photos/technicians'),
            'commission_percent' => $validated['commission_percent'] ?? config('workshop.default_technician_commission_percent', 20),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->modalSuccess('Teknisi berhasil ditambahkan.', $technician);
    }

    public function show(Technician $technician): JsonResponse
    {
        return response()->json(['data' => $technician]);
    }

    public function update(Request $request, Technician $technician): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ], $this->photoRules()));

        $technician->update([
            ...collect($validated)->except(['photo', 'remove_photo'])->all(),
            'photo' => $this->resolvePhotoPath($request, 'photos/technicians', $technician->photo),
            'commission_percent' => $validated['commission_percent'] ?? config('workshop.default_technician_commission_percent', 20),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->modalSuccess('Teknisi berhasil diperbarui.', $technician->fresh());
    }

    public function destroy(Technician $technician): JsonResponse|RedirectResponse
    {
        $technician->deletePhotoFile();
        $technician->delete();

        return $this->modalSuccess('Teknisi berhasil dihapus.');
    }
}
