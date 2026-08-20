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
        <div class="data-panel-head">
            <div class="data-panel-icon"><i class="bi bi-funnel"></i></div>
            <div class="flex-grow-1">
                <h3 class="data-panel-title">Filter Periode</h3>
                <p class="data-panel-desc">
                    Periode aktif:
                    <strong>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</strong>
                    —
                    <strong>{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</strong>
                </p>
            </div>
        </div>
        <div class="data-panel-body pt-3">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control form-control-clean" value="{{ $from }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control form-control-clean" value="{{ $to }}" required>
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Terapkan Filter
                    </button>
                    <a href="{{ route('financial-reports.export-pdf', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    @include('financial-reports.partials.report-content-web')

    <div class="modal fade" id="commission-detail-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="commission-detail-title">Rincian Komisi Teknisi</h5>
                        <div class="small text-muted" id="commission-detail-period"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="commission-detail-loading" class="text-center text-muted py-4">Memuat rincian...</div>
                    <div id="commission-detail-error" class="alert alert-danger d-none mb-0"></div>
                    <div id="commission-detail-content" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle fr-data-table mb-0">
                                <thead>
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th>Jasa</th>
                                        <th class="text-end">Nilai Jasa</th>
                                        <th class="text-end">Komisi</th>
                                    </tr>
                                </thead>
                                <tbody id="commission-detail-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between flex-wrap gap-2">
                    <div class="small text-muted" id="commission-detail-count"></div>
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <div class="fw-semibold text-info" id="commission-detail-total"></div>
                        <a href="#" id="commission-detail-print" class="btn btn-outline-secondary btn-sm d-none" target="_blank">
                            <i class="bi bi-printer me-1"></i> Cetak
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('css/financial-report.css') }}?v={{ filemtime(public_path('css/financial-report.css')) }}">
@endpush

@push('js')
    <script>
        (function ($) {
            const urlTemplate = @json(route('financial-reports.technician-commissions', ['technician' => '__ID__']));
            const printUrlTemplate = @json(route('financial-reports.technician-commissions-pdf', ['technician' => '__ID__']));
            const from = @json($from);
            const to = @json($to);

            function formatRp(n) {
                return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
            }

            function escapeHtml(value) {
                return $('<div>').text(value == null ? '' : String(value)).html();
            }

            function buildPrintUrl(technicianId) {
                return printUrlTemplate.replace('__ID__', technicianId)
                    + '?from=' + encodeURIComponent(from)
                    + '&to=' + encodeURIComponent(to);
            }

            $('.fr-commission-link').on('click', function () {
                const technicianId = $(this).data('technician-id');
                const technicianName = $(this).data('technician-name');
                const $modal = $('#commission-detail-modal');
                const $loading = $('#commission-detail-loading');
                const $error = $('#commission-detail-error');
                const $content = $('#commission-detail-content');
                const $body = $('#commission-detail-body');
                const $print = $('#commission-detail-print');

                $('#commission-detail-title').text('Rincian Komisi — ' + technicianName);
                $('#commission-detail-period').text('Periode ' + from.split('-').reverse().join('/') + ' – ' + to.split('-').reverse().join('/'));
                $('#commission-detail-count').text('');
                $('#commission-detail-total').text('');
                $print.addClass('d-none').attr('href', '#');
                $loading.removeClass('d-none');
                $error.addClass('d-none').text('');
                $content.addClass('d-none');
                $body.empty();

                bootstrap.Modal.getOrCreateInstance($modal[0]).show();

                $.get(urlTemplate.replace('__ID__', technicianId), { from: from, to: to })
                    .done(function (res) {
                        const data = res.data || {};
                        const rows = data.transactions || [];

                        if (!rows.length) {
                            $body.append('<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada transaksi komisi pada periode ini.</td></tr>');
                        } else {
                            rows.forEach(function (row, index) {
                                $body.append(
                                    '<tr>' +
                                        '<td>' + (index + 1) + '. <span class="fw-semibold">' + escapeHtml(row.transaction_no) + '</span></td>' +
                                        '<td>' + escapeHtml(row.created_at) + '</td>' +
                                        '<td>' + escapeHtml(row.customer_name) + '</td>' +
                                        '<td>' + escapeHtml(row.services_label) + '</td>' +
                                        '<td class="text-end">' + formatRp(row.services_total) + '</td>' +
                                        '<td class="text-end fw-semibold text-info">' + formatRp(row.commission) + '</td>' +
                                    '</tr>'
                                );
                            });
                        }

                        $('#commission-detail-count').text((data.transaction_count || 0) + ' transaksi');
                        $('#commission-detail-total').text('Total komisi ' + formatRp(data.commission_total));
                        $print.attr('href', buildPrintUrl(technicianId)).removeClass('d-none');
                        $loading.addClass('d-none');
                        $content.removeClass('d-none');
                    })
                    .fail(function (xhr) {
                        $loading.addClass('d-none');
                        $error.removeClass('d-none').text(xhr.responseJSON?.message || 'Gagal memuat rincian komisi.');
                    });
            });
        })(jQuery);
    </script>
@endpush
