<?php

namespace App\Http\Controllers;

use App\Support\PermissionGroups;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:role view')->only(['index', 'show']);
        $this->middleware('permission:role create')->only(['create', 'store']);
        $this->middleware('permission:role edit')->only(['edit', 'update']);
        $this->middleware('permission:role delete')->only('destroy');
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return DataTables::of(Role::query()->latest('id'))
                ->addIndexColumn()
                ->addColumn('users_count', fn (Role $role) => $role->users()->count())
                ->addColumn('action', 'roles.include.action')
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('roles.index');
    }

    public function create(): View
    {
        $this->syncConfigPermissions();

        return view('roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->syncConfigPermissions();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function show(Role $role): View
    {
        $role->load('permissions');

        return view('roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $this->syncConfigPermissions();
        $role->load('permissions');

        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->syncConfigPermissions();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($role->name === 'Super Admin' && $validated['name'] !== 'Super Admin') {
            return back()->with('error', 'Nama role Super Admin tidak dapat diubah.');
        }

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Super Admin') {
            return back()->with('error', 'Role Super Admin tidak dapat dihapus.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Role tidak dapat dihapus karena masih digunakan user.');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    private function syncConfigPermissions(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        foreach (PermissionGroups::names() as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }
    }
}
