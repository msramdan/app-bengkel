@extends('layouts.app')

@section('title', 'Pemasukan Manual')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Pemasukan Manual'],
        ],
        'title' => 'Pemasukan Manual',
        'subtitle' => 'Catat pemasukan kas di luar transaksi penjualan.',
    ])

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Daftar Pemasukan</h2>
            @can('manual income create')
                <button type="button" class="btn btn-primary btn-add" data-action="create">
                    <i class="bi bi-plus-lg"></i> Tambah Pemasukan
                </button>
            @endcan
        </div>
        <div class="data-panel-body">
            @include('manual-cash-entries.partials.filters')
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="data-table" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Entri</th>
                            <th class="text-end">Nominal</th>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Metode Bayar</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="static" id="form-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="crud-form" method="post" action="{{ route('manual-incomes.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" data-modal-title>Tambah Pemasukan Manual</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="form-hint-sm mb-3"><i class="bi bi-info-circle me-1"></i> Nomor entri dibuat otomatis (contoh: <code>MIN-20260702-0001</code>). Data ini masuk ke laporan keuangan.</p>
                        @include('manual-cash-entries.partials.form-fields', ['type' => 'income'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-action" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="static" id="show-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content tx-detail-modal">
                <div class="modal-header tx-detail-modal__header">
                    <div>
                        <p class="tx-detail-modal__eyebrow mb-0">Detail Pemasukan</p>
                        <h5 class="modal-title tx-detail-modal__title mb-0" id="show-entry-no">—</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body tx-detail-modal__body"></div>
                <div class="modal-footer tx-detail-modal__footer">
                    <button type="button" class="btn btn-light-action" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css">
@endpush

@push('js')
    <script src="{{ asset('js/manual-cash-entry.js') }}"></script>
    <script>
        AthaManualCashEntry.init({
            table: '#data-table',
            ajaxUrl: '{{ route('manual-incomes.index') }}',
            storeUrl: '{{ route('manual-incomes.store') }}',
            showUrlTemplate: '{{ route('manual-incomes.show', '__ID__') }}',
            cancelUrlTemplate: '{{ route('manual-incomes.destroy', '__ID__') }}',
            canCancel: @json(auth()->user()->can('manual income cancel')),
            entryType: 'income',
        });
    </script>
@endpush
