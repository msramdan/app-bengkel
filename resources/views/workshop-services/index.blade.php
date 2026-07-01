@extends('layouts.app')

@section('title', 'Master Jasa Servis')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Master Jasa'],
        ],
        'title' => 'Master Jasa Servis',
        'subtitle' => 'Kelola daftar jasa dan harga servis bengkel.',
    ])

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Daftar Jasa</h2>
            @can('workshop service create')
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
                            <th>Kode</th>
                            <th>Nama Jasa</th>
                            <th>Harga</th>
                            <th>Status</th>
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
                        <h5 class="modal-title" data-modal-title>Tambah Jasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="form-hint-sm mb-3"><i class="bi bi-info-circle me-1"></i> Kode jasa dibuat otomatis (contoh: <code>JSV-20260701-0001</code>).</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Jasa <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-clean" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Harga <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control form-control-clean" min="0" step="0.01" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control form-control-clean" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
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
                    <h5 class="modal-title">Detail Jasa</h5>
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
            ajax: '{{ route('workshop-services.index') }}',
            order: [[5, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                { data: 'price_fmt', name: 'price', orderable: false },
                { data: 'status', name: 'is_active', orderable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
        });

        AthaModalCrud.init({
            storeUrl: '{{ route('workshop-services.store') }}',
            updateUrlTemplate: '{{ route('workshop-services.update', '__ID__') }}',
            showUrlTemplate: '{{ route('workshop-services.show', '__ID__') }}',
            destroyUrlTemplate: '{{ route('workshop-services.destroy', '__ID__') }}',
            entityName: 'Jasa',
            renderShow: function (d) {
                const price = Number(d.price || 0).toLocaleString('id-ID');
                const status = d.is_active ? 'Aktif' : 'Nonaktif';
                return `
                    <dl class="detail-list mb-0">
                        <dt>Kode</dt><dd>${d.code}</dd>
                        <dt>Nama</dt><dd>${d.name}</dd>
                        <dt>Harga</dt><dd>Rp ${price}</dd>
                        <dt>Deskripsi</dt><dd>${d.description || '-'}</dd>
                        <dt>Status</dt><dd>${status}</dd>
                    </dl>`;
            },
        });
    </script>
@endpush
