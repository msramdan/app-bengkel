@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $kpis = $kpis ?? [];
        $charts = $charts ?? [];
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $monthLabel = now()->translatedFormat('F Y');
    @endphp

    @include('layouts.partials.page-hero', [
        'items' => [['label' => 'Dashboard']],
        'title' => 'Dashboard',
        'subtitle' => brand_name() . ' — Ringkasan operasional ' . $monthLabel,
    ])

    {{-- KPI utama --}}
    <div class="row g-3 mb-4">
        @can('transaction view')
            <div class="col-6 col-xl-3">
                <div class="dash-kpi dash-kpi-primary">
                    <div class="dash-kpi-icon"><i class="bi bi-cash-stack"></i></div>
                    <div class="dash-kpi-body">
                        <div class="dash-kpi-label">Pemasukan Hari Ini</div>
                        <div class="dash-kpi-value">{{ $rp($kpis['revenue_today'] ?? 0) }}</div>
                        <div class="dash-kpi-meta">{{ $kpis['transactions_today'] ?? 0 }} transaksi</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="dash-kpi dash-kpi-success">
                    <div class="dash-kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="dash-kpi-body">
                        <div class="dash-kpi-label">Pemasukan Bulan Ini</div>
                        <div class="dash-kpi-value">{{ $rp($kpis['revenue_month'] ?? 0) }}</div>
                        <div class="dash-kpi-meta">{{ $monthLabel }}</div>
                    </div>
                </div>
            </div>
        @endcan
        @can('purchase view')
            <div class="col-6 col-xl-3">
                <div class="dash-kpi dash-kpi-warning">
                    <div class="dash-kpi-icon"><i class="bi bi-cart-dash"></i></div>
                    <div class="dash-kpi-body">
                        <div class="dash-kpi-label">Pengeluaran Bulan Ini</div>
                        <div class="dash-kpi-value">{{ $rp($kpis['expense_month'] ?? 0) }}</div>
                        <div class="dash-kpi-meta">{{ $kpis['purchases_month'] ?? 0 }} pembelian</div>
                    </div>
                </div>
            </div>
        @endcan
        @can('financial report view')
            <div class="col-6 col-xl-3">
                <div class="dash-kpi dash-kpi-info">
                    <div class="dash-kpi-icon"><i class="bi bi-piggy-bank"></i></div>
                    <div class="dash-kpi-body">
                        <div class="dash-kpi-label">Estimasi Sisa Kas</div>
                        <div class="dash-kpi-value">{{ $rp($kpis['profit_estimate_month'] ?? 0) }}</div>
                        <div class="dash-kpi-meta">Setelah pembelian & komisi</div>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    {{-- KPI operasional --}}
    <div class="row g-3 mb-4">
        @can('item view')
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card dash-mini-stat">
                    <div class="dash-mini-icon text-primary"><i class="bi bi-box-seam"></i></div>
                    <div class="stat-label">Barang Aktif</div>
                    <div class="stat-value accent">{{ number_format($kpis['items_active'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card dash-mini-stat {{ ($kpis['low_stock'] ?? 0) > 0 ? 'dash-mini-stat-danger' : '' }}">
                    <div class="dash-mini-icon text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="stat-label">Stok Menipis</div>
                    <div class="stat-value {{ ($kpis['low_stock'] ?? 0) > 0 ? 'text-danger' : 'accent' }}">{{ $kpis['low_stock'] ?? 0 }}</div>
                </div>
            </div>
        @endcan
        @can('customer view')
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card dash-mini-stat">
                    <div class="dash-mini-icon text-info"><i class="bi bi-people"></i></div>
                    <div class="stat-label">Pelanggan</div>
                    <div class="stat-value accent">{{ number_format($kpis['customers'] ?? 0) }}</div>
                </div>
            </div>
        @endcan
        @can('technician view')
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card dash-mini-stat">
                    <div class="dash-mini-icon text-secondary"><i class="bi bi-wrench-adjustable"></i></div>
                    <div class="stat-label">Teknisi Aktif</div>
                    <div class="stat-value accent">{{ $kpis['technicians_active'] ?? 0 }}</div>
                </div>
            </div>
        @endcan
        @can('workshop service view')
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card dash-mini-stat">
                    <div class="dash-mini-icon text-warning"><i class="bi bi-tools"></i></div>
                    <div class="stat-label">Master Jasa</div>
                    <div class="stat-value accent">{{ $kpis['services_active'] ?? 0 }}</div>
                </div>
            </div>
        @endcan
        @can('transaction view')
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card dash-mini-stat">
                    <div class="dash-mini-icon text-success"><i class="bi bi-percent"></i></div>
                    <div class="stat-label">Komisi Bulan Ini</div>
                    <div class="stat-value accent" style="font-size:0.95rem">{{ $rp($kpis['commission_month'] ?? 0) }}</div>
                </div>
            </div>
        @endcan
    </div>

    @can('transaction view')
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="data-panel h-100">
                    <div class="data-panel-head data-panel-head-row">
                        <h2 class="data-panel-title"><i class="bi bi-bar-chart-line me-1"></i> Tren 7 Hari Terakhir</h2>
                        <span class="text-muted small">Pemasukan vs pengeluaran</span>
                    </div>
                    <div class="data-panel-body">
                        <div class="dash-chart-wrap">
                            <canvas id="chart-daily-trend" height="120"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="data-panel h-100">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-pie-chart me-1"></i> Metode Bayar</h2>
                        <span class="text-muted small d-block">Penjualan {{ $monthLabel }}</span>
                    </div>
                    <div class="data-panel-body d-flex align-items-center justify-content-center">
                        <div class="dash-chart-donut">
                            <canvas id="chart-payment-methods"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="data-panel h-100">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-diagram-3 me-1"></i> Jenis Transaksi</h2>
                        <span class="text-muted small d-block">{{ $monthLabel }}</span>
                    </div>
                    <div class="data-panel-body">
                        <div class="dash-chart-wrap" style="max-height:220px">
                            <canvas id="chart-transaction-types"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="data-panel h-100">
                    <div class="data-panel-head data-panel-head-row">
                        <h2 class="data-panel-title"><i class="bi bi-lightning me-1"></i> Akses Cepat</h2>
                    </div>
                    <div class="data-panel-body">
                        <div class="row g-2">
                            @can('transaction create')
                                <div class="col-6 col-md-4">
                                    <a href="{{ route('transactions.create') }}" class="dash-quick-link">
                                        <i class="bi bi-plus-circle"></i>
                                        <span>Transaksi Baru</span>
                                    </a>
                                </div>
                            @endcan
                            @can('purchase create')
                                <div class="col-6 col-md-4">
                                    <a href="{{ route('purchases.create') }}" class="dash-quick-link">
                                        <i class="bi bi-bag-plus"></i>
                                        <span>Pembelian Barang</span>
                                    </a>
                                </div>
                            @endcan
                            @can('financial report view')
                                <div class="col-6 col-md-4">
                                    <a href="{{ route('financial-reports.index') }}" class="dash-quick-link">
                                        <i class="bi bi-graph-up"></i>
                                        <span>Laporan Keuangan</span>
                                    </a>
                                </div>
                            @endcan
                            @can('stock report view')
                                <div class="col-6 col-md-4">
                                    <a href="{{ route('stock-reports.index') }}" class="dash-quick-link">
                                        <i class="bi bi-clipboard-data"></i>
                                        <span>Laporan Stok</span>
                                    </a>
                                </div>
                            @endcan
                            @can('customer create')
                                <div class="col-6 col-md-4">
                                    <a href="{{ route('customers.index') }}" class="dash-quick-link">
                                        <i class="bi bi-person-plus"></i>
                                        <span>Data Pelanggan</span>
                                    </a>
                                </div>
                            @endcan
                            @can('settings view')
                                <div class="col-6 col-md-4">
                                    <a href="{{ route('settings.edit') }}" class="dash-quick-link">
                                        <i class="bi bi-gear"></i>
                                        <span>Pengaturan</span>
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="row g-4 mb-4">
        @can('item view')
            <div class="col-lg-{{ auth()->user()->can('transaction view') ? '6' : '12' }}">
                @if (($kpis['low_stock'] ?? 0) > 0)
                    <div class="dashboard-alert dashboard-alert-warning h-100">
                        <div class="dashboard-alert-head">
                            <div class="dashboard-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                            <div>
                                <div class="dashboard-alert-title">Stok Menipis</div>
                                <div class="dashboard-alert-sub">{{ $kpis['low_stock'] }} barang perlu restock.</div>
                            </div>
                            @can('stock report view')
                                <a href="{{ route('stock-reports.index') }}" class="btn btn-sm btn-outline-danger ms-auto">Lihat</a>
                            @endcan
                        </div>
                        <div class="dashboard-alert-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Barang</th>
                                            <th class="text-center">Stok</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($low_stock_items as $item)
                                            <tr>
                                                <td>
                                                    <div class="fw-medium small">{{ $item->name }}</div>
                                                    <div class="text-muted" style="font-size:0.7rem">{{ $item->code }}</div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger-subtle text-danger">{{ $item->stock }}</span>
                                                </td>
                                                <td>
                                                    @if ($item->stock === 0)
                                                        <span class="badge bg-danger">Habis</span>
                                                    @else
                                                        <span class="badge bg-warning-subtle text-warning">Menipis</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="dashboard-alert dashboard-alert-ok h-100 d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Semua stok barang aman.
                    </div>
                @endif
            </div>
        @endcan

        @can('transaction view')
            <div class="col-lg-6">
                <div class="data-panel h-100">
                    <div class="data-panel-head data-panel-head-row">
                        <h2 class="data-panel-title"><i class="bi bi-clock-history me-1"></i> Transaksi Terbaru</h2>
                        <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-light">Semua</a>
                    </div>
                    <div class="data-panel-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Pelanggan</th>
                                        <th class="text-end">Total</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recent_transactions as $tx)
                                        <tr>
                                            <td class="fw-medium small">{{ $tx->transaction_no }}</td>
                                            <td class="small">{{ $tx->customer?->name ?? '-' }}</td>
                                            <td class="text-end small fw-semibold text-success">{{ $rp($tx->total) }}</td>
                                            <td class="text-muted small">{{ $tx->created_at?->format('d/m H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4 small">Belum ada transaksi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection

@can('transaction view')
    @push('js')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const chartFont = { family: "'Segoe UI', system-ui, sans-serif", size: 11 };
                const gridColor = 'rgba(148, 163, 184, 0.2)';
                const daily = @json($charts['daily'] ?? ['labels' => [], 'revenue' => [], 'expense' => []]);
                const payments = @json($charts['payment_methods'] ?? ['labels' => [], 'values' => [], 'colors' => []]);
                const types = @json($charts['transaction_types'] ?? ['labels' => [], 'values' => [], 'colors' => []]);

                new Chart(document.getElementById('chart-daily-trend'), {
                    type: 'line',
                    data: {
                        labels: daily.labels,
                        datasets: [
                            {
                                label: 'Pemasukan',
                                data: daily.revenue,
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.12)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Pengeluaran',
                                data: daily.expense,
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.08)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { font: chartFont, usePointStyle: true } },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        return ctx.dataset.label + ': Rp ' + Number(ctx.raw || 0).toLocaleString('id-ID');
                                    },
                                },
                            },
                        },
                        scales: {
                            x: { grid: { color: gridColor }, ticks: { font: chartFont } },
                            y: {
                                grid: { color: gridColor },
                                ticks: {
                                    font: chartFont,
                                    callback: function (v) { return 'Rp ' + Number(v).toLocaleString('id-ID'); },
                                },
                            },
                        },
                    },
                });

                new Chart(document.getElementById('chart-payment-methods'), {
                    type: 'doughnut',
                    data: {
                        labels: payments.labels,
                        datasets: [{
                            data: payments.values,
                            backgroundColor: payments.colors,
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        cutout: '62%',
                        plugins: {
                            legend: { position: 'bottom', labels: { font: chartFont, padding: 12, usePointStyle: true } },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        return ctx.label + ': Rp ' + Number(ctx.raw || 0).toLocaleString('id-ID');
                                    },
                                },
                            },
                        },
                    },
                });

                new Chart(document.getElementById('chart-transaction-types'), {
                    type: 'bar',
                    data: {
                        labels: types.labels,
                        datasets: [{
                            label: 'Jumlah',
                            data: types.values,
                            backgroundColor: types.colors,
                            borderRadius: 6,
                            borderSkipped: false,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: chartFont } },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { font: chartFont, stepSize: 1 },
                            },
                        },
                    },
                });
            })();
        </script>
    @endpush
@endcan
