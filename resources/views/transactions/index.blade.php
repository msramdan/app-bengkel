@extends('layouts.app')

@section('title', 'Transaksi Penjualan')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Transaksi Penjualan'],
        ],
        'title' => 'Transaksi Penjualan',
        'subtitle' => 'Penjualan barang, servis jasa, dan transaksi gabungan.',
    ])

    @php
        $today = now()->toDateString();
        $presetHari = ['from' => $today, 'to' => $today];
        $presetMinggu = ['from' => now()->startOfWeek()->toDateString(), 'to' => $today];
        $presetBulan = ['from' => now()->startOfMonth()->toDateString(), 'to' => $today];
        $presetTahun = ['from' => now()->startOfYear()->toDateString(), 'to' => $today];
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    @endphp

    <div class="data-panel mb-4">
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-funnel"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Filter Periode</h3>
                <p class="data-panel-desc mb-0">
                    Periode aktif:
                    <strong>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</strong>
                    —
                    <strong>{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</strong>
                    <span class="text-muted">· {{ number_format($periodStats['count']) }} transaksi · omzet {{ $rp($periodStats['completed_total']) }}</span>
                </p>
            </div>
        </div>
        <div class="data-panel-body pt-3">
            <form method="get" action="{{ route('transactions.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-clean" value="{{ $from }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-clean" value="{{ $to }}" required>
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Terapkan Filter
                    </button>
                    <a href="{{ route('transactions.export-pdf', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                    </a>
                </div>
            </form>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route('transactions.index', $presetHari) }}" class="btn btn-sm btn-light">Hari ini</a>
                <a href="{{ route('transactions.index', $presetMinggu) }}" class="btn btn-sm btn-light">Minggu ini</a>
                <a href="{{ route('transactions.index', $presetBulan) }}" class="btn btn-sm btn-light">Bulan ini</a>
                <a href="{{ route('transactions.index', $presetTahun) }}" class="btn btn-sm btn-light">Tahun ini</a>
            </div>
        </div>
    </div>

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Riwayat Penjualan</h2>
            @can('transaction create')
                <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Transaksi Baru
                </a>
            @endcan
        </div>
        <div class="data-panel-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="data-table" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Transaksi</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Pelanggan</th>
                            <th>Teknisi</th>
                            <th>Total</th>
                            <th>Metode Bayar</th>
                            <th>Komisi Teknisi</th>
                            <th>Kasir</th>
                            <th>Waktu</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="static" id="show-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content tx-detail-modal">
                <div class="modal-header tx-detail-modal__header">
                    <div>
                        <p class="tx-detail-modal__eyebrow mb-0">Detail Transaksi</p>
                        <h5 class="modal-title tx-detail-modal__title mb-0" id="tx-detail-modal-title">—</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body tx-detail-modal__body"></div>
                <div class="modal-footer tx-detail-modal__footer">
                    <a href="#" id="btn-print-invoice" class="btn btn-primary d-none" target="_blank">
                        <i class="bi bi-printer"></i> Cetak Invoice
                    </a>
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
    <script src="{{ asset('js/transaction-detail.js') }}"></script>
    <script>
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('transactions.index', ['from' => $from, 'to' => $to]) }}',
            order: [[10, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'transaction_no', name: 'transaction_no' },
                { data: 'type_label', name: 'type', orderable: false },
                { data: 'status_label', name: 'status', orderable: false, searchable: false },
                { data: 'customer_name', name: 'customer.name', orderable: false },
                { data: 'technician_name', name: 'technician.name', orderable: false },
                { data: 'total_fmt', name: 'total', orderable: false, searchable: false },
                { data: 'payment_label', name: 'payment_method', orderable: false },
                { data: 'commission_fmt', name: 'technician_commission', orderable: false, searchable: false },
                { data: 'user_name', name: 'user.name', orderable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
        });

        AthaTransactionDetail.init({
            table: '#data-table',
            showModal: '#show-modal',
            showUrl: '{{ route('transactions.show', '__ID__') }}',
            invoiceUrl: '{{ route('transactions.invoice', '__ID__') }}',
            cancelUrlTemplate: '{{ route('transactions.destroy', '__ID__') }}',
            canCancel: @json(auth()->user()->can('transaction delete')),
            techPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            ownerPercent: {{ 100 - (int) config('workshop.default_technician_commission_percent', 20) }},
        });
    </script>
@endpush
