@extends('layouts.app')

@section('title', 'Akun Bank')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Akun Bank'],
        ],
        'title' => 'Akun Bank',
        'subtitle' => 'Kelola rekening bank untuk pembayaran transfer.',
    ])

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Daftar Akun Bank</h2>
            @can('bank account create')
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
                            <th>Bank</th>
                            <th>Nama Akun</th>
                            <th>No. Rekening</th>
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
                        <h5 class="modal-title" data-modal-title>Tambah Akun Bank</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Bank <span class="text-danger">*</span></label>
                                <input type="text" name="bank_name" class="form-control form-control-clean" placeholder="Contoh: BRI" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nama Pemilik Rekening <span class="text-danger">*</span></label>
                                <input type="text" name="account_name" class="form-control form-control-clean" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">No. Rekening <span class="text-danger">*</span></label>
                                <input type="text" name="account_number" class="form-control form-control-clean" required>
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
                    <h5 class="modal-title">Detail Akun Bank</h5>
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
            ajax: '{{ route('bank-accounts.index') }}',
            order: [[5, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'bank_name', name: 'bank_name' },
                { data: 'account_name', name: 'account_name' },
                { data: 'account_number', name: 'account_number' },
                { data: 'status', name: 'is_active', orderable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
        });

        AthaModalCrud.init({
            storeUrl: '{{ route('bank-accounts.store') }}',
            updateUrlTemplate: '{{ route('bank-accounts.update', '__ID__') }}',
            showUrlTemplate: '{{ route('bank-accounts.show', '__ID__') }}',
            destroyUrlTemplate: '{{ route('bank-accounts.destroy', '__ID__') }}',
            entityName: 'Akun Bank',
            renderShow: function (d) {
                return `<dl class="detail-list mb-0">
                    <dt>Bank</dt><dd>${d.bank_name}</dd>
                    <dt>Nama Akun</dt><dd>${d.account_name}</dd>
                    <dt>No. Rekening</dt><dd>${d.account_number}</dd>
                    <dt>Status</dt><dd>${d.is_active ? 'Aktif' : 'Nonaktif'}</dd>
                </dl>`;
            },
        });
    </script>
@endpush
