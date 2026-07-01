@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Role & Permission', 'url' => route('roles.index')],
            ['label' => 'Edit Role'],
        ],
        'title' => 'Edit Role',
        'subtitle' => $role->name,
        'backUrl' => route('roles.index'),
    ])

    <form action="{{ route('roles.update', $role) }}" method="POST" class="form-page-inner">
        @csrf
        @method('PUT')
        @include('roles.include.form', ['role' => $role])
        @include('layouts.partials.form-actions', [
            'backUrl' => route('roles.index'),
            'submitLabel' => 'Simpan Perubahan',
        ])
    </form>
@endsection
