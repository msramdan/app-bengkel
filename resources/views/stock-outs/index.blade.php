@extends('layouts.app')

@section('title', 'Stok Keluar')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Stok Keluar'],
        ],
        'title' => 'Stok Keluar',
        'subtitle' => 'Catat pengurangan stok — bisa multi barang sekaligus.',
    ])

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Riwayat Stok Keluar</h2>
            @can('stock out create')
                <button type="button" class="btn btn-primary btn-add" data-action="create">
                    <i class="bi bi-plus-lg"></i> Stok Keluar
                </button>
            @endcan
        </div>
        <div class="data-panel-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="data-table" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Transaksi</th>
                            <th>Barang</th>
                            <th>Total Qty</th>
                            <th>Stok</th>
                            <th>Sumber</th>
                            <th>Petugas</th>
                            <th>Waktu</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('stock.partials.cart-modal', ['title' => 'Stok Keluar', 'items' => $items])
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css">
@endpush

@push('js')
    <script src="{{ asset('js/stock-cart.js') }}"></script>
    <script>
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('stock-outs.index') }}',
            order: [[7, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'batch_no_display', name: 'batch_no' },
                { data: 'items_label', name: 'batch_no', orderable: false },
                { data: 'quantity', name: 'total_quantity', orderable: false, searchable: false },
                { data: 'stock_change', orderable: false, searchable: false },
                { data: 'source_label', name: 'reference_no', orderable: false },
                { data: 'user_name', name: 'user_id', orderable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
        });

        AthaStockCart.init({
            storeUrl: '{{ route('stock-outs.store') }}',
            batchShowUrl: '{{ route('stock-outs.batch', '__BATCH__') }}',
            items: @json($items),
            type: 'out',
        });
    </script>
@endpush
