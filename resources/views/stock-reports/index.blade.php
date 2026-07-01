@extends('layouts.app')

@section('title', 'Laporan Stok')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Laporan Stok'],
        ],
        'title' => 'Laporan Stok',
        'subtitle' => 'Monitor kondisi stok barang secara real-time.',
    ])

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Total Barang</div>
                <div class="stat-value">{{ number_format($stats['total_items']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Stok Menipis</div>
                <div class="stat-value text-warning">{{ number_format($stats['low_stock']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Stok Habis</div>
                <div class="stat-value text-danger">{{ number_format($stats['out_of_stock']) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Nilai Stok (HPP)</div>
                <div class="stat-value" style="font-size:1.1rem">Rp {{ number_format($stats['total_stock_value'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="data-panel">
        <div class="data-panel-head">
            <h2 class="data-panel-title">Kondisi Stok Barang</h2>
        </div>
        <div class="data-panel-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="data-table" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th>Stock Opname</th>
                            <th>Status</th>
                            <th>Harga Jual</th>
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
            ajax: '{{ route('stock-reports.index') }}',
            order: [[9, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                { data: 'category_name', name: 'category.name', orderable: false },
                { data: 'unit_name', name: 'unit.name', orderable: false },
                { data: 'stock_display', name: 'stock' },
                { data: 'stock_opname', name: 'stock_opname', searchable: false },
                { data: 'status', name: 'is_active', orderable: false, searchable: false },
                { data: 'selling_price', name: 'selling_price', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at', visible: false, searchable: false },
            ],
        });
    </script>
@endpush
