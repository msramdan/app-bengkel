@extends('layouts.app')

@section('title', 'Detail Role')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Role & Permission', 'url' => route('roles.index')],
            ['label' => 'Detail'],
        ],
        'title' => $role->name,
        'subtitle' => $role->users()->count() . ' pengguna · ' . $role->permissions->count() . ' permission',
        'backUrl' => route('roles.index'),
    ])

    <div class="form-panel form-panel-clean">
        <div class="form-panel-body">
            @if ($role->permissions->isEmpty())
                <p class="text-muted mb-0">Belum ada permission.</p>
            @else
                <div class="perm-tag-list">
                    @foreach ($role->permissions as $permission)
                        <span class="perm-tag">{{ ucwords($permission->name) }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="form-actions mt-3">
        @can('role edit')
            <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary btn-save">
                <i class="bi bi-pencil me-1"></i> Edit Role
            </a>
        @endcan
    </div>
@endsection
