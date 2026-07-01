@extends('layouts.app')

@section('title', 'Tambah Role')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Role & Permission', 'url' => route('roles.index')],
            ['label' => 'Tambah Role'],
        ],
        'title' => 'Tambah Role',
        'subtitle' => 'Buat role dan atur permission.',
        'backUrl' => route('roles.index'),
    ])

    <form action="{{ route('roles.store') }}" method="POST" class="form-page-inner">
        @csrf
        @include('roles.include.form')
        @include('layouts.partials.form-actions', [
            'backUrl' => route('roles.index'),
            'submitLabel' => 'Simpan Role',
        ])
    </form>
@endsection
