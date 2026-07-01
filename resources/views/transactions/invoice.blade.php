@extends('layouts.print')

@section('title', 'Nota '.$transaction->transaction_no)

@section('content')
    @php
        $paymentLabel = \App\Support\PaymentMethodResolver::label($transaction->payment_method);
        if ($transaction->payment_method === 'transfer' && $transaction->bankAccount) {
            $paymentLabel .= ' — '.$transaction->bankAccount->displayLabel();
        }
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');

        $lines = collect();
        foreach ($transaction->items as $item) {
            $lines->push([
                'name' => $item->item_name,
                'type' => 'part',
                'type_label' => 'Sparepart',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ]);
        }
        foreach ($transaction->serviceLines as $line) {
            $lines->push([
                'name' => $line->service_name,
                'type' => 'service',
                'type_label' => 'Jasa',
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'subtotal' => $line->subtotal,
            ]);
        }
    @endphp

    <div class="invoice-toolbar no-print">
        <button type="button" class="btn btn-print" onclick="window.print()">Cetak</button>
        <button type="button" class="btn btn-close-page" onclick="window.close()">Tutup</button>
    </div>

    <article class="invoice-sheet">
        <div class="invoice-accent-bar"></div>

        <div class="invoice-inner">
            <header class="invoice-top">
                <div class="invoice-brand">
                    <h1>{{ brand_name() }}</h1>
                    <p class="tagline">{{ brand_tagline() }}</p>
                </div>
                <div class="invoice-doc">
                    <p class="invoice-doc-label">Bukti Pembayaran</p>
                    <h2 class="invoice-doc-title">Nota Penjualan</h2>
                    <span class="invoice-doc-no">{{ $transaction->transaction_no }}</span>
                </div>
            </header>

            <div class="invoice-info-grid">
                <div class="invoice-card">
                    <p class="invoice-card-label">Pelanggan</p>
                    <p class="invoice-customer-name">{{ $transaction->displayCustomerName() }}</p>
                    <p class="invoice-customer-meta">
                        @if ($transaction->customer?->phone)
                            {{ $transaction->customer->phone }}<br>
                        @endif
                        @if ($transaction->customer?->address)
                            {{ $transaction->customer->address }}
                        @endif
                    </p>
                </div>
                <div class="invoice-card">
                    <p class="invoice-card-label">Detail Transaksi</p>
                    <ul class="invoice-meta-list">
                        <li>
                            <span>Tanggal</span>
                            <span>{{ $transaction->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                        </li>
                        <li>
                            <span>Metode Bayar</span>
                            <span>{{ $paymentLabel }}</span>
                        </li>
                        <li>
                            <span>Status</span>
                            <span><span class="invoice-badge-paid">LUNAS</span></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="invoice-table-wrap">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="col-no">#</th>
                            <th>Keterangan</th>
                            <th class="col-qty">Qty</th>
                            <th class="col-price">Harga</th>
                            <th class="col-sub">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $index => $line)
                            <tr>
                                <td class="col-no">{{ $index + 1 }}</td>
                                <td>
                                    <div class="invoice-item-name">{{ $line['name'] }}</div>
                                    <span class="invoice-item-type type-{{ $line['type'] }}">{{ $line['type_label'] }}</span>
                                </td>
                                <td class="col-qty">{{ number_format($line['quantity']) }}</td>
                                <td class="col-price">{{ $rp($line['unit_price']) }}</td>
                                <td class="col-sub">{{ $rp($line['subtotal']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;color:var(--inv-muted);padding:1.5rem">Tidak ada rincian.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="invoice-bottom">
                <div class="invoice-totals">
                    @if ((float) $transaction->subtotal_items > 0 && (float) $transaction->subtotal_services > 0)
                        <div class="invoice-totals-row">
                            <span>Subtotal Barang</span>
                            <span>{{ $rp($transaction->subtotal_items) }}</span>
                        </div>
                        <div class="invoice-totals-row">
                            <span>Subtotal Jasa</span>
                            <span>{{ $rp($transaction->subtotal_services) }}</span>
                        </div>
                    @endif
                    @if ((float) $transaction->discount > 0)
                        <div class="invoice-totals-row discount">
                            <span>Diskon</span>
                            <span>- {{ $rp($transaction->discount) }}</span>
                        </div>
                    @endif
                    <div class="invoice-totals-grand">
                        <span>Total Bayar</span>
                        <span>{{ $rp($transaction->total) }}</span>
                    </div>
                </div>
            </div>

            <footer class="invoice-thanks">
                <p>Terima kasih telah berkunjung ke {{ brand_name() }}.</p>
                <small>Nota ini merupakan bukti pembayaran yang sah.</small>
            </footer>
        </div>
    </article>
@endsection

@push('js')
    @if (request()->boolean('print'))
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endif
@endpush
