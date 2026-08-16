@php
    $sales = $report['sales'];
    $purchases = $report['purchases'];
    $manualIncome = $report['manual_income'];
    $manualExpense = $report['manual_expense'];
    $totals = $report['totals'];
    $profit = $report['profit'];
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

{{-- KPI ringkasan --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="dash-kpi dash-kpi-success">
            <div class="dash-kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="dash-kpi-body">
                <div class="dash-kpi-label">Pemasukan Penjualan</div>
                <div class="dash-kpi-value text-success">{{ $rp($sales['revenue']) }}</div>
                <div class="dash-kpi-meta">{{ $sales['transaction_count'] }} transaksi + manual {{ $rp($manualIncome['amount']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dash-kpi dash-kpi-warning">
            <div class="dash-kpi-icon"><i class="bi bi-cart-dash"></i></div>
            <div class="dash-kpi-body">
                <div class="dash-kpi-label">Pengeluaran Operasional</div>
                <div class="dash-kpi-value text-warning">{{ $rp($purchases['expense'] + $manualExpense['amount']) }}</div>
                <div class="dash-kpi-meta">{{ $purchases['purchase_count'] }} pembelian + {{ $manualExpense['entry_count'] }} manual</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dash-kpi dash-kpi-info">
            <div class="dash-kpi-icon"><i class="bi bi-person-gear"></i></div>
            <div class="dash-kpi-body">
                <div class="dash-kpi-label">Komisi Teknisi</div>
                <div class="dash-kpi-value text-info">{{ $rp($sales['technician_commission']) }}</div>
                <div class="dash-kpi-meta">Sesuai % komisi masing-masing teknisi</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dash-kpi fr-kpi-owner">
            <div class="dash-kpi-icon"><i class="bi bi-piggy-bank"></i></div>
            <div class="dash-kpi-body">
                <div class="dash-kpi-label">Estimasi Laba Owner</div>
                <div class="dash-kpi-value">{{ $rp($profit['owner_net_estimate']) }}</div>
                <div class="dash-kpi-meta">Margin sparepart + jasa owner − pengeluaran manual</div>
            </div>
        </div>
    </div>
</div>

{{-- Rincian pemasukan & pembagian --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="data-panel h-100">
            <div class="data-panel-head">
                <div class="data-panel-icon"><i class="bi bi-receipt"></i></div>
                <div>
                    <h3 class="data-panel-title">Rincian Pemasukan Penjualan</h3>
                    <p class="data-panel-desc">Breakdown pendapatan dari sparepart dan jasa servis</p>
                </div>
            </div>
            <div class="data-panel-body pt-3">
                <table class="table table-borderless fr-detail-table mb-0">
                    <tbody>
                        <tr>
                            <td>Penjualan Sparepart</td>
                            <td class="text-end">{{ $rp($sales['items_revenue']) }}</td>
                        </tr>
                        <tr>
                            <td>Jasa Servis</td>
                            <td class="text-end">{{ $rp($sales['services_revenue']) }}</td>
                        </tr>
                        <tr class="fr-subtotal-row">
                            <td class="fw-semibold">Subtotal Kotor</td>
                            <td class="text-end fw-semibold">{{ $rp($sales['gross']) }}</td>
                        </tr>
                        <tr>
                            <td class="text-danger">Diskon</td>
                            <td class="text-end text-danger">− {{ $rp($sales['discount']) }}</td>
                        </tr>
                        <tr class="fr-total-row-web">
                            <td class="fw-bold">Pemasukan Bersih</td>
                            <td class="text-end fw-bold text-success">{{ $rp($sales['revenue']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="data-panel h-100">
            <div class="data-panel-head">
                <div class="data-panel-icon"><i class="bi bi-pie-chart"></i></div>
                <div>
                    <h3 class="data-panel-title">Pembagian Penjualan</h3>
                    <p class="data-panel-desc">Alokasi pendapatan antara teknisi dan owner</p>
                </div>
            </div>
            <div class="data-panel-body pt-3">
                <table class="table table-borderless fr-detail-table mb-0">
                    <tbody>
                        <tr>
                            <td>Komisi Teknisi (dari jasa)</td>
                            <td class="text-end">{{ $rp($sales['technician_commission']) }}</td>
                        </tr>
                        <tr>
                            <td>Bagian Owner — Jasa</td>
                            <td class="text-end">{{ $rp($sales['owner_service_share']) }}</td>
                        </tr>
                        <tr>
                            <td>HPP Sparepart (harga beli)</td>
                            <td class="text-end">{{ $rp($sales['items_cost'] ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td>Bagian Owner — Sparepart (margin)</td>
                            <td class="text-end">{{ $rp($sales['owner_items_share']) }}</td>
                        </tr>
                        <tr class="fr-total-row-web">
                            <td class="fw-bold">Total Bagian Owner</td>
                            <td class="text-end fw-bold">{{ $rp($sales['owner_share']) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="fr-hint-web mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Margin sparepart = harga jual − harga beli. Komisi hanya dari jasa servis.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Pemasukan & pengeluaran manual --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="data-panel h-100">
            <div class="data-panel-head">
                <div class="data-panel-icon"><i class="bi bi-plus-circle"></i></div>
                <div>
                    <h3 class="data-panel-title">Pemasukan Manual</h3>
                    <p class="data-panel-desc">Di luar transaksi penjualan — total {{ $rp($manualIncome['amount']) }}</p>
                </div>
            </div>
            <div class="data-panel-body">
                <table class="table table-sm table-hover align-middle fr-data-table mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($manualIncome['by_category'] as $row)
                            <tr>
                                <td>{{ $row['category_name'] }}</td>
                                <td class="text-center">{{ $row['entry_count'] }}</td>
                                <td class="text-end text-success">{{ $rp($row['amount_total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada pemasukan manual.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="data-panel h-100">
            <div class="data-panel-head">
                <div class="data-panel-icon"><i class="bi bi-dash-circle"></i></div>
                <div>
                    <h3 class="data-panel-title">Pengeluaran Manual</h3>
                    <p class="data-panel-desc">Operasional — total {{ $rp($manualExpense['amount']) }}</p>
                </div>
            </div>
            <div class="data-panel-body">
                <table class="table table-sm table-hover align-middle fr-data-table mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($manualExpense['by_category'] as $row)
                            <tr>
                                <td>{{ $row['category_name'] }}</td>
                                <td class="text-center">{{ $row['entry_count'] }}</td>
                                <td class="text-end text-warning">{{ $rp($row['amount_total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada pengeluaran manual.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Komisi per teknisi --}}
<div class="data-panel mb-4">
    <div class="data-panel-head">
        <div class="data-panel-icon"><i class="bi bi-people"></i></div>
        <div>
            <h3 class="data-panel-title">Komisi per Teknisi</h3>
            <p class="data-panel-desc">Rincian komisi berdasarkan transaksi jasa pada periode ini</p>
        </div>
    </div>
    <div class="data-panel-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle fr-data-table mb-0">
                <thead>
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
                            <td class="fw-semibold">{{ $row['technician_name'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $row['transaction_count'] }}</span>
                            </td>
                            <td class="text-end">{{ $rp($row['services_total']) }}</td>
                            <td class="text-end fw-semibold">
                                @if (! empty($row['technician_id']))
                                    <button type="button"
                                        class="btn btn-link p-0 text-info fw-semibold text-decoration-none fr-commission-link"
                                        data-technician-id="{{ $row['technician_id'] }}"
                                        data-technician-name="{{ $row['technician_name'] }}"
                                        title="Lihat rincian transaksi komisi">
                                        {{ $rp($row['commission_total']) }}
                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                    </button>
                                @else
                                    <span class="text-info">{{ $rp($row['commission_total']) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada komisi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($report['commissions']->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total Komisi</td>
                            <td class="text-end fw-bold text-info">{{ $rp($sales['technician_commission']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if ($report['commissions']->isNotEmpty())
            <p class="fr-hint-web mb-0 mt-3">
                <i class="bi bi-info-circle me-1"></i>
                Klik nominal komisi untuk melihat daftar transaksi (nomor, jasa, dan nilai komisi).
            </p>
        @endif
    </div>
</div>

@php $inflows = $report['payment_sources']['inflows']; @endphp
<div class="data-panel mb-4">
    <div class="data-panel-head">
        <div class="data-panel-icon"><i class="bi bi-arrow-down-circle"></i></div>
        <div>
            <h3 class="data-panel-title">Sumber Dana — Pemasukan (Penjualan + Manual)</h3>
            <p class="data-panel-desc">Pembagian pembayaran masuk per metode bayar</p>
        </div>
    </div>
    <div class="data-panel-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="fr-source-tile">
                    <div class="fr-source-tile-label">{{ $inflows['labels']['cash'] }}</div>
                    <div class="fr-source-tile-value text-success">{{ $rp($inflows['cash']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fr-source-tile">
                    <div class="fr-source-tile-label">{{ $inflows['labels']['qris'] }}</div>
                    <div class="fr-source-tile-value text-success">{{ $rp($inflows['qris']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fr-source-tile">
                    <div class="fr-source-tile-label">{{ $inflows['labels']['transfer'] }}</div>
                    <div class="fr-source-tile-value text-success">{{ $rp($inflows['transfer_total']) }}</div>
                </div>
            </div>
        </div>
        @if (! empty($inflows['transfer']))
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle fr-data-table mb-0">
                    <thead>
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
        <div class="fr-footer-total">
            <span>Total Pemasukan per Sumber Dana</span>
            <span class="text-success fw-bold">{{ $rp($inflows['total']) }}</span>
        </div>
    </div>
</div>

@php $outflows = $report['payment_sources']['outflows']; @endphp
<div class="data-panel mb-4">
    <div class="data-panel-head">
        <div class="data-panel-icon"><i class="bi bi-arrow-up-circle"></i></div>
        <div>
            <h3 class="data-panel-title">Sumber Dana — Pengeluaran (Pembelian + Manual)</h3>
            <p class="data-panel-desc">Pembagian pembayaran keluar per metode bayar</p>
        </div>
    </div>
    <div class="data-panel-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="fr-source-tile">
                    <div class="fr-source-tile-label">{{ $outflows['labels']['cash'] }}</div>
                    <div class="fr-source-tile-value text-warning">{{ $rp($outflows['cash']) }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="fr-source-tile">
                    <div class="fr-source-tile-label">{{ $outflows['labels']['transfer'] }} (Akun Bank)</div>
                    <div class="fr-source-tile-value text-warning">{{ $rp($outflows['transfer_total']) }}</div>
                </div>
            </div>
        </div>
        @if (! empty($outflows['transfer']))
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle fr-data-table mb-0">
                    <thead>
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
        <div class="fr-footer-total">
            <span>Total Pengeluaran per Sumber Dana</span>
            <span class="text-warning fw-bold">{{ $rp($outflows['total']) }}</span>
        </div>
    </div>
</div>

{{-- Arus kas --}}
<div class="data-panel mb-0">
    <div class="data-panel-head">
        <div class="data-panel-icon"><i class="bi bi-wallet2"></i></div>
        <div>
            <h3 class="data-panel-title">Ringkasan Arus Kas Estimasi</h3>
            <p class="data-panel-desc">Perkiraan sisa kas setelah pemasukan, pengeluaran, dan komisi</p>
        </div>
    </div>
    <div class="data-panel-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="fr-cash-tile fr-cash-tile-in">
                    <div class="fr-cash-tile-label">Total pemasukan</div>
                    <div class="fr-cash-tile-value text-success">{{ $rp($totals['inflow']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fr-cash-tile fr-cash-tile-out">
                    <div class="fr-cash-tile-label">Total pengeluaran + komisi</div>
                    <div class="fr-cash-tile-value text-danger">− {{ $rp($totals['operating_outflow']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fr-cash-tile fr-cash-tile-net">
                    <div class="fr-cash-tile-label">Estimasi sisa kas</div>
                    <div class="fr-cash-tile-value">{{ $rp($profit['cash_flow_estimate']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
