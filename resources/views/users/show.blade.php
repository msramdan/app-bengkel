@extends('layouts.app')

@section('title', 'Detail User')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Data User', 'url' => route('users.index')],
            ['label' => 'Detail'],
        ],
        'title' => $user->name,
        'subtitle' => $user->email,
        'backUrl' => route('users.index'),
    ])

    <div class="form-panel form-panel-clean">
        <div class="form-panel-body">
            <dl class="detail-list detail-list-clean">
                <div class="detail-item">
                    <dt>Role</dt>
                    <dd>
                        @forelse ($user->roles as $role)
                            <span class="detail-badge">{{ $role->name }}</span>
                        @empty
                            -
                        @endforelse
                    </dd>
                </div>
                <div class="detail-item">
                    <dt>Terdaftar</dt>
                    <dd>{{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div class="detail-item">
                    <dt>Terakhir diubah</dt>
                    <dd>{{ $user->updated_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="form-actions mt-3">
        @can('user edit')
            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-save">
                <i class="bi bi-pencil me-1"></i> Edit User
            </a>
        @endcan
    </div>
@endsection
