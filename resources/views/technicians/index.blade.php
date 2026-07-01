@extends('layouts.app')

@section('title', 'Data Teknisi')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Teknisi'],
        ],
        'title' => 'Data Teknisi',
        'subtitle' => 'Kelola data teknisi bengkel.',
    ])

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Daftar Teknisi</h2>
            @can('technician create')
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
                            <th>Foto</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Telepon</th>
                            <th>Komisi</th>
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-content-clean">
                <form id="crud-form" method="post" action="" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" data-modal-title>Tambah Teknisi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="form-hint-sm mb-3"><i class="bi bi-info-circle me-1"></i> Kode teknisi dibuat otomatis (contoh: <code>TKN-20260701-0001</code>).</p>
                        <div class="row g-3">
                            @include('layouts.partials.entity-photo-field', ['label' => 'Teknisi', 'placeholderIcon' => 'bi-person'])
                            <div class="col-md-6">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-clean" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control form-control-clean">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control form-control-clean">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Keahlian</label>
                                <input type="text" name="specialty" class="form-control form-control-clean" placeholder="Mis. Mesin, Kelistrikan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Komisi (%)</label>
                                <input type="number" name="commission_percent" class="form-control form-control-clean" min="0" max="100" step="0.01" value="{{ (int) config('workshop.default_technician_commission_percent', 20) }}">
                                <div class="form-hint-sm">Persentase komisi teknisi dari total jasa servis.</div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control form-control-clean" rows="2"></textarea>
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-content-clean">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Teknisi</h5>
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
            ajax: '{{ route('technicians.index') }}',
            order: [[7, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'photo_thumb', orderable: false, searchable: false },
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                { data: 'phone', name: 'phone' },
                { data: 'commission_percent', name: 'commission_percent', orderable: false, searchable: false },
                { data: 'status', name: 'is_active', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
        });

        AthaModalCrud.init({
            storeUrl: '{{ route('technicians.store') }}',
            updateUrlTemplate: '{{ route('technicians.update', '__ID__') }}',
            showUrlTemplate: '{{ route('technicians.show', '__ID__') }}',
            destroyUrlTemplate: '{{ route('technicians.destroy', '__ID__') }}',
            entityName: 'Teknisi',
            onFormReset: function (mode) {
                if (mode === 'create') {
                    $('#is_active').prop('checked', true);
                    $('input[name="commission_percent"]').val({{ (int) config('workshop.default_technician_commission_percent', 20) }});
                }
            },
            renderShow: function (d) {
                const photo = d.photo_url
                    ? `<div class="text-center mb-3"><img src="${d.photo_url}" alt="${d.name}" class="entity-photo-detail"></div>`
                    : '';
                return photo + `
                    <dl class="detail-list mb-0">
                        <dt>Kode</dt><dd>${d.code}</dd>
                        <dt>Nama</dt><dd>${d.name}</dd>
                        <dt>Telepon</dt><dd>${d.phone || '-'}</dd>
                        <dt>Email</dt><dd>${d.email || '-'}</dd>
                        <dt>Keahlian</dt><dd>${d.specialty || '-'}</dd>
                        <dt>Komisi</dt><dd>${parseFloat(d.commission_percent).toFixed(0)}%</dd>
                        <dt>Status</dt><dd>${d.is_active ? 'Aktif' : 'Nonaktif'}</dd>
                        <dt>Catatan</dt><dd>${d.notes || '-'}</dd>
                    </dl>`;
            },
        });
    </script>
@endpush
