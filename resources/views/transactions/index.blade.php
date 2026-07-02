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
            ajax: '{{ route('transactions.index') }}',
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
            techPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            ownerPercent: {{ 100 - (int) config('workshop.default_technician_commission_percent', 20) }},
        });

        $('#data-table').on('click', '[data-action="cancel-held"]', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Batalkan open order?',
                text: 'Draft order akan dihapus dari daftar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Tidak',
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                $.ajax({
                    url: '{{ route('transactions.hold.cancel', '__ID__') }}'.replace('__ID__', id),
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    headers: { Accept: 'application/json' },
                    success: function (res) {
                        Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                        $('#data-table').DataTable().ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Gagal membatalkan open order.' });
                    },
                });
            });
        });
    </script>
@endpush
