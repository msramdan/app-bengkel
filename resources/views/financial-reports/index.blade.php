@extends('layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
    @php
        $sales = $report['sales'];
        $purchases = $report['purchases'];
        $profit = $report['profit'];
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    @endphp

    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Laporan Keuangan'],
        ],
        'title' => 'Laporan Keuangan',
        'subtitle' => 'Pemasukan penjualan, komisi teknisi, dan pengeluaran pembelian.',
    ])

    <div class="data-panel mb-4">
        <div class="data-panel-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-clean" value="{{ $from }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-clean" value="{{ $to }}" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="text-muted small">Periode: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Pemasukan Bersih</div>
                <div class="stat-value text-success" style="font-size:1.15rem">{{ $rp($sales['revenue']) }}</div>
                <div class="text-muted small">{{ $sales['transaction_count'] }} transaksi penjualan</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Pengeluaran Pembelian</div>
                <div class="stat-value text-warning" style="font-size:1.15rem">{{ $rp($purchases['expense']) }}</div>
                <div class="text-muted small">{{ $purchases['purchase_count'] }} pembelian</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Komisi Teknisi</div>
                <div class="stat-value text-info" style="font-size:1.15rem">{{ $rp($sales['technician_commission']) }}</div>
                <div class="text-muted small">Sesuai % komisi masing-masing teknisi</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Estimasi Laba Owner</div>
                <div class="stat-value" style="font-size:1.15rem">{{ $rp($profit['owner_net_estimate']) }}</div>
                <div class="text-muted small">Bagian owner − pembelian</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="data-panel h-100">
                <div class="data-panel-head">
                    <h2 class="data-panel-title">Rincian Pemasukan Penjualan</h2>
                </div>
                <div class="data-panel-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td>Penjualan Sparepart</td><td class="text-end">{{ $rp($sales['items_revenue']) }}</td></tr>
                            <tr><td>Jasa Servis</td><td class="text-end">{{ $rp($sales['services_revenue']) }}</td></tr>
                            <tr class="fw-medium"><td>Subtotal Kotor</td><td class="text-end">{{ $rp($sales['gross']) }}</td></tr>
                            <tr><td class="text-danger">Diskon</td><td class="text-end text-danger">- {{ $rp($sales['discount']) }}</td></tr>
                            <tr class="fw-bold border-top"><td>Pemasukan Bersih</td><td class="text-end text-success">{{ $rp($sales['revenue']) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="data-panel h-100">
                <div class="data-panel-head">
                    <h2 class="data-panel-title">Pembagian Penjualan</h2>
                </div>
                <div class="data-panel-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td>Komisi Teknisi (dari jasa)</td><td class="text-end">{{ $rp($sales['technician_commission']) }}</td></tr>
                            <tr><td>Bagian Owner — Jasa</td><td class="text-end">{{ $rp($sales['owner_service_share']) }}</td></tr>
                            <tr><td>Bagian Owner — Sparepart (100%)</td><td class="text-end">{{ $rp($sales['owner_items_share']) }}</td></tr>
                            <tr class="fw-bold border-top"><td>Total Bagian Owner</td><td class="text-end">{{ $rp($sales['owner_share']) }}</td></tr>
                        </tbody>
                    </table>
                    <p class="form-hint-sm mt-3 mb-0">Sparepart 100% untuk toko. Komisi dihitung dari total jasa, bukan dari harga barang.</p>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="data-panel">
                <div class="data-panel-head">
                    <h2 class="data-panel-title">Komisi per Teknisi</h2>
                </div>
                <div class="data-panel-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Teknisi</th>
                                    <th class="text-center">Jumlah Transaksi</th>
                                    <th class="text-end">Total Jasa</th>
                                    <th class="text-end">Total Komisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['commissions'] as $row)
                                    <tr>
                                        <td class="fw-medium">{{ $row['technician_name'] }}</td>
                                        <td class="text-center">{{ $row['transaction_count'] }}</td>
                                        <td class="text-end">{{ $rp($row['services_total']) }}</td>
                                        <td class="text-end fw-semibold text-info">{{ $rp($row['commission_total']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">Belum ada komisi pada periode ini.</td></tr>
                                @endforelse
                            </tbody>
                            @if ($report['commissions']->isNotEmpty())
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="3" class="text-end">Total Komisi</td>
                                        <td class="text-end text-info">{{ $rp($sales['technician_commission']) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="data-panel">
                <div class="data-panel-head">
                    <h2 class="data-panel-title">Sumber Dana — Pemasukan (Penjualan)</h2>
                </div>
                <div class="data-panel-body">
                    @php $inflows = $report['payment_sources']['inflows']; @endphp
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $inflows['labels']['cash'] }}</div>
                                <div class="fs-5 fw-semibold text-success">{{ $rp($inflows['cash']) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $inflows['labels']['qris'] }}</div>
                                <div class="fs-5 fw-semibold text-success">{{ $rp($inflows['qris']) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $inflows['labels']['transfer'] }}</div>
                                <div class="fs-5 fw-semibold text-success">{{ $rp($inflows['transfer_total']) }}</div>
                            </div>
                        </div>
                    </div>
                    @if (! empty($inflows['transfer']))
                        <div class="table-responsive mt-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Akun Bank</th>
                                        <th class="text-center">Jumlah Transaksi</th>
                                        <th class="text-end">Total Masuk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inflows['transfer'] as $row)
                                        <tr>
                                            <td>{{ $row['bank_label'] }}</td>
                                            <td class="text-center">{{ $row['count'] }}</td>
                                            <td class="text-end text-success">{{ $rp($row['amount']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-3">
                        <span>Total Pemasukan per Sumber Dana</span>
                        <span class="text-success">{{ $rp($inflows['total']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="data-panel">
                <div class="data-panel-head">
                    <h2 class="data-panel-title">Sumber Dana — Pengeluaran (Pembelian)</h2>
                </div>
                <div class="data-panel-body">
                    @php $outflows = $report['payment_sources']['outflows']; @endphp
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $outflows['labels']['cash'] }}</div>
                                <div class="fs-5 fw-semibold text-warning">{{ $rp($outflows['cash']) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $outflows['labels']['transfer'] }} (Akun Bank)</div>
                                <div class="fs-5 fw-semibold text-warning">{{ $rp($outflows['transfer_total']) }}</div>
                            </div>
                        </div>
                    </div>
                    @if (! empty($outflows['transfer']))
                        <div class="table-responsive mt-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Akun Bank</th>
                                        <th class="text-center">Jumlah Pembelian</th>
                                        <th class="text-end">Total Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($outflows['transfer'] as $row)
                                        <tr>
                                            <td>{{ $row['bank_label'] }}</td>
                                            <td class="text-center">{{ $row['count'] }}</td>
                                            <td class="text-end text-warning">{{ $rp($row['amount']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-3">
                        <span>Total Pengeluaran per Sumber Dana</span>
                        <span class="text-warning">{{ $rp($outflows['total']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="data-panel">
                <div class="data-panel-head">
                    <h2 class="data-panel-title">Ringkasan Arus Kas Estimasi</h2>
                </div>
                <div class="data-panel-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="text-muted small">Pemasukan dari pelanggan</div>
                                <div class="fs-5 fw-semibold text-success">{{ $rp($sales['revenue']) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="text-muted small">Pengeluaran + komisi</div>
                                <div class="fs-5 fw-semibold text-danger">- {{ $rp($purchases['expense'] + $sales['technician_commission']) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small">Estimasi sisa kas</div>
                                <div class="fs-5 fw-bold">{{ $rp($profit['cash_flow_estimate']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
