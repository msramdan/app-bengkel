@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Data User', 'url' => route('users.index')],
            ['label' => 'Tambah User'],
        ],
        'title' => 'Tambah User',
        'subtitle' => 'Buat akun pengguna baru.',
        'backUrl' => route('users.index'),
    ])

    <form action="{{ route('users.store') }}" method="POST" class="form-page-inner">
        @csrf
        @include('users.include.form')
        @include('layouts.partials.form-actions', [
            'backUrl' => route('users.index'),
            'submitLabel' => 'Simpan User',
        ])
    </form>
@endsection
