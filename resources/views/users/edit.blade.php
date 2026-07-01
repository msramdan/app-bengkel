@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Data User', 'url' => route('users.index')],
            ['label' => 'Edit User'],
        ],
        'title' => 'Edit User',
        'subtitle' => $user->name,
        'backUrl' => route('users.index'),
    ])

    <form action="{{ route('users.update', $user) }}" method="POST" class="form-page-inner">
        @csrf
        @method('PUT')
        @include('users.include.form', ['user' => $user])
        @include('layouts.partials.form-actions', [
            'backUrl' => route('users.index'),
            'submitLabel' => 'Simpan Perubahan',
        ])
    </form>
@endsection
