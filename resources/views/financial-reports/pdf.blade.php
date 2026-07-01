<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan {{ \Carbon\Carbon::parse($from)->format('d-m-Y') }} - {{ brand_name() }}</title>
    <link rel="stylesheet" href="{{ public_path('css/financial-report-pdf.css') }}">
</head>
<body>
    <div class="fr-pdf-header">
        <h1>{{ brand_name() }}</h1>
        <p>{{ brand_tagline() }}</p>
        <div class="fr-pdf-period">
            Laporan Keuangan — Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </div>

    @include('financial-reports.partials.report-content')

    <div class="fr-pdf-footer">
        Dicetak pada {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }} — {{ brand_name() }}
    </div>
</body>
</html>
