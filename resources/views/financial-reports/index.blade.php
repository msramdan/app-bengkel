@extends('layouts.app')

@section('title', 'Laporan Keuangan')

@section('content')
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
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('financial-reports.export-pdf', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="text-muted small">Periode: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</span>
                </div>
            </form>
        </div>
    </div>

    @include('financial-reports.partials.report-content')
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('css/financial-report.css') }}">
@endpush
