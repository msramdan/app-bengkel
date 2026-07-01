@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Edit Profil'],
        ],
        'title' => 'Edit Profil',
        'subtitle' => 'Kelola informasi akun dan keamanan login Anda.',
        'backUrl' => route('dashboard'),
    ])

    <div class="profile-layout">
        <aside class="profile-aside">
            <div class="profile-card">
                <div class="profile-card-banner"></div>
                <div class="profile-card-body">
                    <div class="profile-avatar">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <h2 class="profile-name">{{ $user->name }}</h2>
                    <p class="profile-email">{{ $user->email }}</p>
                    <span class="profile-role-badge">
                        {{ $user->roles->pluck('name')->first() ?: 'User' }}
                    </span>
                    <ul class="profile-meta list-unstyled">
                        <li>
                            <i class="bi bi-calendar3"></i>
                            <span>Bergabung {{ $user->created_at?->format('d/m/Y') ?? '-' }}</span>
                        </li>
                        <li>
                            <i class="bi bi-shield-check"></i>
                            <span>Akun aktif</span>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <div class="profile-main">
            <form action="{{ route('profile.update') }}" method="POST" class="form-page-inner">
                @csrf
                @method('PUT')
                @include('profile.include.form', ['user' => $user])
                @include('layouts.partials.form-actions', [
                    'backUrl' => route('dashboard'),
                    'submitLabel' => 'Simpan Perubahan',
                ])
            </form>
        </div>
    </div>
@endsection
