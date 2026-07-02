@php
    $sales = $report['sales'];
    $purchases = $report['purchases'];
    $manualIncome = $report['manual_income'];
    $manualExpense = $report['manual_expense'];
    $totals = $report['totals'];
    $profit = $report['profit'];
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

<div class="fr-summary">
    <table class="fr-summary-table" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="fr-stat-card">
                <div class="fr-stat-label">Pemasukan Penjualan</div>
                <div class="fr-stat-value fr-text-success">{{ $rp($sales['revenue']) }}</div>
                <div class="fr-stat-meta">{{ $sales['transaction_count'] }} transaksi + manual {{ $rp($manualIncome['amount']) }}</div>
            </td>
            <td class="fr-stat-card">
                <div class="fr-stat-label">Pengeluaran Operasional</div>
                <div class="fr-stat-value fr-text-warning">{{ $rp($purchases['expense'] + $manualExpense['amount']) }}</div>
                <div class="fr-stat-meta">{{ $purchases['purchase_count'] }} pembelian + {{ $manualExpense['entry_count'] }} manual</div>
            </td>
            <td class="fr-stat-card">
                <div class="fr-stat-label">Komisi Teknisi</div>
                <div class="fr-stat-value fr-text-info">{{ $rp($sales['technician_commission']) }}</div>
                <div class="fr-stat-meta">Sesuai % komisi masing-masing teknisi</div>
            </td>
            <td class="fr-stat-card">
                <div class="fr-stat-label">Estimasi Laba Owner</div>
                <div class="fr-stat-value">{{ $rp($profit['owner_net_estimate']) }}</div>
                <div class="fr-stat-meta">Pemasukan − pengeluaran − komisi</div>
            </td>
        </tr>
    </table>
</div>

<table class="fr-split" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td class="fr-panel" width="50%" valign="top">
            <div class="fr-panel-head">Rincian Pemasukan Penjualan</div>
            <div class="fr-panel-body">
                <table class="fr-table fr-table-simple" width="100%">
                    <tbody>
                        <tr><td>Penjualan Sparepart</td><td class="text-end">{{ $rp($sales['items_revenue']) }}</td></tr>
                        <tr><td>Jasa Servis</td><td class="text-end">{{ $rp($sales['services_revenue']) }}</td></tr>
                        <tr class="fw-medium"><td>Subtotal Kotor</td><td class="text-end">{{ $rp($sales['gross']) }}</td></tr>
                        <tr><td class="fr-text-danger">Diskon</td><td class="text-end fr-text-danger">- {{ $rp($sales['discount']) }}</td></tr>
                        <tr class="fr-total-row"><td>Pemasukan Bersih</td><td class="text-end fr-text-success">{{ $rp($sales['revenue']) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </td>
        <td width="12"></td>
        <td class="fr-panel" width="50%" valign="top">
            <div class="fr-panel-head">Pembagian Penjualan</div>
            <div class="fr-panel-body">
                <table class="fr-table fr-table-simple" width="100%">
                    <tbody>
                        <tr><td>Komisi Teknisi (dari jasa)</td><td class="text-end">{{ $rp($sales['technician_commission']) }}</td></tr>
                        <tr><td>Bagian Owner — Jasa</td><td class="text-end">{{ $rp($sales['owner_service_share']) }}</td></tr>
                        <tr><td>Bagian Owner — Sparepart (100%)</td><td class="text-end">{{ $rp($sales['owner_items_share']) }}</td></tr>
                        <tr class="fr-total-row"><td>Total Bagian Owner</td><td class="text-end">{{ $rp($sales['owner_share']) }}</td></tr>
                    </tbody>
                </table>
                <p class="fr-hint">Sparepart 100% untuk toko. Komisi dihitung dari total jasa, bukan dari harga barang.</p>
            </div>
        </td>
    </tr>
</table>

<table class="fr-split" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td class="fr-panel" width="50%" valign="top">
            <div class="fr-panel-head">Pemasukan Manual ({{ $rp($manualIncome['amount']) }})</div>
            <div class="fr-panel-body">
                <table class="fr-table" width="100%">
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
                                <td class="text-end fr-text-success">{{ $rp($row['amount_total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center fr-muted">Belum ada pemasukan manual.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </td>
        <td width="12"></td>
        <td class="fr-panel" width="50%" valign="top">
            <div class="fr-panel-head">Pengeluaran Manual ({{ $rp($manualExpense['amount']) }})</div>
            <div class="fr-panel-body">
                <table class="fr-table" width="100%">
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
                                <td class="text-end fr-text-warning">{{ $rp($row['amount_total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center fr-muted">Belum ada pengeluaran manual.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="fr-panel fr-panel-block">
    <div class="fr-panel-head">Komisi per Teknisi</div>
    <div class="fr-panel-body">
        <table class="fr-table" width="100%">
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
                        <td class="fw-medium">{{ $row['technician_name'] }}</td>
                        <td class="text-center">{{ $row['transaction_count'] }}</td>
                        <td class="text-end">{{ $rp($row['services_total']) }}</td>
                        <td class="text-end fw-semibold fr-text-info">{{ $rp($row['commission_total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center fr-muted">Belum ada komisi pada periode ini.</td></tr>
                @endforelse
            </tbody>
            @if ($report['commissions']->isNotEmpty())
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total Komisi</td>
                        <td class="text-end fr-text-info">{{ $rp($sales['technician_commission']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

@php $inflows = $report['payment_sources']['inflows']; @endphp
<div class="fr-panel fr-panel-block">
    <div class="fr-panel-head">Sumber Dana — Pemasukan (Penjualan + Manual)</div>
    <div class="fr-panel-body">
        <table class="fr-source-grid" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="fr-source-box">
                    <div class="fr-source-label">{{ $inflows['labels']['cash'] }}</div>
                    <div class="fr-source-value fr-text-success">{{ $rp($inflows['cash']) }}</div>
                </td>
                <td width="8"></td>
                <td class="fr-source-box">
                    <div class="fr-source-label">{{ $inflows['labels']['qris'] }}</div>
                    <div class="fr-source-value fr-text-success">{{ $rp($inflows['qris']) }}</div>
                </td>
                <td width="8"></td>
                <td class="fr-source-box">
                    <div class="fr-source-label">{{ $inflows['labels']['transfer'] }}</div>
                    <div class="fr-source-value fr-text-success">{{ $rp($inflows['transfer_total']) }}</div>
                </td>
            </tr>
        </table>
        @if (! empty($inflows['transfer']))
            <table class="fr-table fr-table-sm fr-mt" width="100%">
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
                            <td class="text-end fr-text-success">{{ $rp($row['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <div class="fr-summary-line">
            <span>Total Pemasukan per Sumber Dana</span>
            <span class="fr-text-success">{{ $rp($inflows['total']) }}</span>
        </div>
    </div>
</div>

@php $outflows = $report['payment_sources']['outflows']; @endphp
<div class="fr-panel fr-panel-block">
    <div class="fr-panel-head">Sumber Dana — Pengeluaran (Pembelian + Manual)</div>
    <div class="fr-panel-body">
        <table class="fr-source-grid" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="fr-source-box" width="50%">
                    <div class="fr-source-label">{{ $outflows['labels']['cash'] }}</div>
                    <div class="fr-source-value fr-text-warning">{{ $rp($outflows['cash']) }}</div>
                </td>
                <td width="8"></td>
                <td class="fr-source-box" width="50%">
                    <div class="fr-source-label">{{ $outflows['labels']['transfer'] }} (Akun Bank)</div>
                    <div class="fr-source-value fr-text-warning">{{ $rp($outflows['transfer_total']) }}</div>
                </td>
            </tr>
        </table>
        @if (! empty($outflows['transfer']))
            <table class="fr-table fr-table-sm fr-mt" width="100%">
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
                            <td class="text-end fr-text-warning">{{ $rp($row['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <div class="fr-summary-line">
            <span>Total Pengeluaran per Sumber Dana</span>
            <span class="fr-text-warning">{{ $rp($outflows['total']) }}</span>
        </div>
    </div>
</div>

<div class="fr-panel fr-panel-block">
    <div class="fr-panel-head">Ringkasan Arus Kas Estimasi</div>
    <div class="fr-panel-body">
        <table class="fr-source-grid" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="fr-source-box">
                    <div class="fr-source-label">Total pemasukan</div>
                    <div class="fr-source-value fr-text-success">{{ $rp($totals['inflow']) }}</div>
                </td>
                <td width="8"></td>
                <td class="fr-source-box">
                    <div class="fr-source-label">Total pengeluaran + komisi</div>
                    <div class="fr-source-value fr-text-danger">- {{ $rp($totals['operating_outflow']) }}</div>
                </td>
                <td width="8"></td>
                <td class="fr-source-box fr-source-box-highlight">
                    <div class="fr-source-label">Estimasi sisa kas</div>
                    <div class="fr-source-value fw-bold">{{ $rp($profit['cash_flow_estimate']) }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>
