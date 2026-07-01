@extends('layouts.app')

@section('title', 'Satuan Barang')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Satuan Barang'],
        ],
        'title' => 'Satuan Barang',
        'subtitle' => 'Kelola satuan barang inventory.',
    ])

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Daftar Satuan</h2>
            @can('item unit create')
                <button type="button" class="btn btn-primary btn-add" data-action="create">
                    <i class="bi bi-plus-lg"></i> Tambah
                </button>
            @endcan
        </div>
        <div class="data-panel-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="data-table" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Singkatan</th>
                            <th>Jumlah Barang</th>
                            <th>Dibuat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="static" id="form-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-clean">
                <form id="crud-form" method="post" action="">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" data-modal-title>Tambah Satuan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-clean" required placeholder="Mis. Pieces">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Singkatan</label>
                            <input type="text" name="abbreviation" class="form-control form-control-clean" placeholder="Mis. pcs">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="static" id="show-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-clean">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Satuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
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
            ajax: '{{ route('item-units.index') }}',
            order: [[4, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'abbreviation', name: 'abbreviation' },
                { data: 'items_count', name: 'items_count', searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
        });

        AthaModalCrud.init({
            storeUrl: '{{ route('item-units.store') }}',
            updateUrlTemplate: '{{ route('item-units.update', '__ID__') }}',
            showUrlTemplate: '{{ route('item-units.show', '__ID__') }}',
            destroyUrlTemplate: '{{ route('item-units.destroy', '__ID__') }}',
            entityName: 'Satuan',
            renderShow: function (d) {
                return `
                    <dl class="detail-list mb-0">
                        <dt>Nama</dt><dd>${d.name}</dd>
                        <dt>Singkatan</dt><dd>${d.abbreviation || '-'}</dd>
                        <dt>Jumlah Barang</dt><dd>${d.items_count ?? 0}</dd>
                    </dl>`;
            },
        });
    </script>
@endpush
