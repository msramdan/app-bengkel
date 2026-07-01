@extends('layouts.app')

@section('title', 'Role & Permission')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Role & Permission'],
        ],
        'title' => 'Role & Permission',
        'subtitle' => 'Kelola role dan hak akses.',
        'backUrl' => route('dashboard'),
    ])

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Daftar Role</h2>
            @can('role create')
                <a href="{{ route('roles.create') }}" class="btn btn-primary btn-add">
                    <i class="bi bi-plus-lg"></i> Tambah
                </a>
            @endcan
        </div>
        <div class="data-panel-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="data-table" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Role</th>
                            <th>Jumlah User</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css">
@endpush

@push('js')
    <script>
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('roles.index') }}',
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'users_count', name: 'users_count', searchable: false },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
        });
    </script>
@endpush
