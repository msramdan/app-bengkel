<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Penjualan {{ \Carbon\Carbon::parse($from)->format('d-m-Y') }} - {{ brand_name() }}</title>
    <link rel="stylesheet" href="{{ public_path('css/financial-report-pdf.css') }}">
    <style>
        .tx-pdf-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .tx-pdf-table th, .tx-pdf-table td { border-bottom: 1px solid #e2e8f0; padding: 5px 4px; text-align: left; font-size: 8.5px; }
        .tx-pdf-table th { border-bottom: 2px solid #8B1538; font-size: 8px; text-transform: uppercase; }
        .tx-pdf-num { text-align: right; white-space: nowrap; }
        .tx-pdf-empty { text-align: center; color: #64748b; padding: 16px 0; }
    </style>
</head>
<body>
    @php
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
        $typeLabel = fn ($type) => match ($type) {
            'sale' => 'Penjualan',
            'service' => 'Servis',
            'combined' => 'Gabungan',
            default => $type,
        };
    @endphp

    <div class="fr-pdf-header">
        <h1>{{ brand_name() }}</h1>
        <p>{{ brand_tagline() }}</p>
        <div class="fr-pdf-period">
            Riwayat Penjualan — Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </div>

    <div class="fr-summary">
        <table class="fr-summary-table" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="fr-stat-card">
                    <div class="fr-stat-label">Jumlah Transaksi</div>
                    <div class="fr-stat-value">{{ number_format($summary['count']) }}</div>
                    <div class="fr-stat-meta">{{ number_format($summary['completed_count']) }} selesai</div>
                </td>
                <td class="fr-stat-card">
                    <div class="fr-stat-label">Omzet Selesai</div>
                    <div class="fr-stat-value fr-text-success">{{ $rp($summary['total']) }}</div>
                    <div class="fr-stat-meta">Tidak termasuk transaksi batal</div>
                </td>
                <td class="fr-stat-card">
                    <div class="fr-stat-label">Komisi Teknisi</div>
                    <div class="fr-stat-value fr-text-info">{{ $rp($summary['commission']) }}</div>
                    <div class="fr-stat-meta">Periode terpilih</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="tx-pdf-table">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Transaksi</th>
                <th>Waktu</th>
                <th>Jenis</th>
                <th>Status</th>
                <th>Pelanggan</th>
                <th>Teknisi</th>
                <th>Metode</th>
                <th>Kasir</th>
                <th class="tx-pdf-num">Total</th>
                <th class="tx-pdf-num">Komisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $index => $tx)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $tx->transaction_no }}</td>
                    <td>{{ $tx->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                    <td>{{ $typeLabel($tx->type) }}</td>
                    <td>{{ $tx->status === 'cancelled' ? 'Batal' : 'Selesai' }}</td>
                    <td>{{ $tx->displayCustomerName() }}</td>
                    <td>{{ $tx->technician?->name ?? '-' }}</td>
                    <td>{{ $tx->displayPaymentLabel() }}</td>
                    <td>{{ $tx->user?->name ?? '-' }}</td>
                    <td class="tx-pdf-num">{{ $rp($tx->total) }}</td>
                    <td class="tx-pdf-num">{{ $tx->technician_commission > 0 ? $rp($tx->technician_commission) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="tx-pdf-empty">Tidak ada transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="fr-pdf-footer">
        Dicetak pada {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }} — {{ brand_name() }}
    </div>
</body>
</html>
