<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rincian Komisi {{ $detail['technician_name'] }} - {{ brand_name() }}</title>
    <link rel="stylesheet" href="{{ public_path('css/financial-report-pdf.css') }}">
    <style>
        .tx-pdf-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .tx-pdf-table th, .tx-pdf-table td { border-bottom: 1px solid #e2e8f0; padding: 5px 4px; text-align: left; font-size: 8.5px; vertical-align: top; }
        .tx-pdf-table th { border-bottom: 2px solid #8B1538; font-size: 8px; text-transform: uppercase; }
        .tx-pdf-num { text-align: right; white-space: nowrap; }
        .tx-pdf-empty { text-align: center; color: #64748b; padding: 16px 0; }
        .tx-pdf-tech { margin-top: 4px; font-size: 12px; font-weight: 700; }
    </style>
</head>
<body>
    @php
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    @endphp

    <div class="fr-pdf-header">
        <h1>{{ brand_name() }}</h1>
        <p>{{ brand_tagline() }}</p>
        <div class="fr-pdf-period">
            Rincian Kerjaan &amp; Komisi Teknisi
        </div>
        <div class="tx-pdf-tech">{{ $detail['technician_name'] }}</div>
        <div class="fr-pdf-period" style="font-weight:400;margin-top:2px">
            Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </div>

    <div class="fr-summary">
        <table class="fr-summary-table" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="fr-stat-card">
                    <div class="fr-stat-label">Jumlah Transaksi</div>
                    <div class="fr-stat-value">{{ number_format($detail['transaction_count']) }}</div>
                    <div class="fr-stat-meta">Pekerjaan dengan komisi</div>
                </td>
                <td class="fr-stat-card">
                    <div class="fr-stat-label">Nilai Jasa</div>
                    <div class="fr-stat-value">{{ $rp($detail['services_total']) }}</div>
                    <div class="fr-stat-meta">Total jasa periode ini</div>
                </td>
                <td class="fr-stat-card">
                    <div class="fr-stat-label">Total Komisi</div>
                    <div class="fr-stat-value fr-text-info">{{ $rp($detail['commission_total']) }}</div>
                    <div class="fr-stat-meta">Hak teknisi</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="tx-pdf-table">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Transaksi</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Jasa</th>
                <th class="tx-pdf-num">Nilai Jasa</th>
                <th class="tx-pdf-num">Komisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detail['transactions'] as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['transaction_no'] }}</td>
                    <td>{{ $row['created_at'] }}</td>
                    <td>{{ $row['customer_name'] }}</td>
                    <td>{{ $row['services_label'] }}</td>
                    <td class="tx-pdf-num">{{ $rp($row['services_total']) }}</td>
                    <td class="tx-pdf-num">{{ $rp($row['commission']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="tx-pdf-empty">Tidak ada transaksi komisi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="fr-pdf-footer">
        Dicetak pada {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }} — {{ brand_name() }}
    </div>
</body>
</html>
