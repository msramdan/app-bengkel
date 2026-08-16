@extends('layouts.print')

@section('title', 'Nota A4 '.$transaction->transaction_no)
@section('body_class', 'print-a4')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/invoice-a4.css') }}?v={{ filemtime(public_path('css/invoice-a4.css')) }}">
@endpush

@section('content')
    @php
        $paymentLabel = \App\Support\PaymentMethodResolver::label($transaction->payment_method);
        if ($transaction->payment_method === 'transfer' && $transaction->bankAccount) {
            $paymentLabel .= ' — '.$transaction->bankAccount->displayLabel();
        }
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
        $printedAt = now()->timezone(config('app.timezone'))->format('d/m/Y H:i');
        $txDate = $transaction->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');

        $hasItems = (float) $transaction->subtotal_items > 0;
        $hasServices = (float) $transaction->subtotal_services > 0;
        $showSubtotals = $hasItems && $hasServices;

        $customerMeta = collect([
            $transaction->customer?->phone,
            $transaction->customer?->address,
        ])->filter()->implode(' · ');

        $lines = collect();
        foreach ($transaction->items as $item) {
            $lines->push([
                'name' => $item->item_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ]);
        }
        foreach ($transaction->serviceLines as $line) {
            $lines->push([
                'name' => $line->service_name,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'subtotal' => $line->subtotal,
            ]);
        }
    @endphp

    <div class="receipt-toolbar no-print">
        <button type="button" class="btn-receipt" onclick="window.print()">Cetak</button>
        <button type="button" class="btn-receipt btn-receipt--light" onclick="window.close()">Tutup</button>
    </div>

    <article class="invoice-a4">
        <header class="invoice-a4-header">
            <div class="invoice-a4-brand">
                @if (brand_has_custom_logo())
                    <img src="{{ brand_logo_url() }}" alt="{{ brand_name() }}" class="invoice-a4-logo">
                @endif
                <div>
                    <div class="invoice-a4-name">{{ brand_name() }}</div>
                    @if (brand_tagline() !== '')
                        <div class="invoice-a4-tagline">{{ brand_tagline() }}</div>
                    @endif
                    @if (brand_address() !== '')
                        <div class="invoice-a4-meta">{{ brand_address() }}</div>
                    @endif
                    @if (brand_whatsapp() !== '')
                        <div class="invoice-a4-meta">WA: {{ brand_whatsapp() }}</div>
                    @endif
                </div>
            </div>
            <div class="invoice-a4-doc">
                <div class="invoice-a4-doc-title">NOTA PENJUALAN</div>
                <div class="invoice-a4-doc-no">{{ $transaction->transaction_no }}</div>
                <div class="invoice-a4-meta">{{ $txDate }}</div>
                <div class="invoice-a4-status">LUNAS</div>
            </div>
        </header>

        <section class="invoice-a4-parties">
            <div>
                <div class="invoice-a4-label">Pelanggan</div>
                <div class="invoice-a4-strong">{{ $transaction->displayCustomerName() }}</div>
                @if ($customerMeta !== '')
                    <div class="invoice-a4-meta">{{ $customerMeta }}</div>
                @endif
            </div>
            <div>
                <div class="invoice-a4-label">Pembayaran</div>
                <div class="invoice-a4-strong">{{ $paymentLabel }}</div>
                @if ($transaction->technician)
                    <div class="invoice-a4-meta">Teknisi: {{ $transaction->technician->name }}</div>
                @endif
                @if ($transaction->user)
                    <div class="invoice-a4-meta">Kasir: {{ $transaction->user->name }}</div>
                @endif
            </div>
        </section>

        <table class="invoice-a4-table">
            <thead>
                <tr>
                    <th class="invoice-a4-col-no">No</th>
                    <th>Uraian</th>
                    <th class="invoice-a4-num">Qty</th>
                    <th class="invoice-a4-num">Harga</th>
                    <th class="invoice-a4-num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lines as $index => $line)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $line['name'] }}</td>
                        <td class="invoice-a4-num">{{ number_format($line['quantity']) }}</td>
                        <td class="invoice-a4-num">{{ $rp($line['unit_price']) }}</td>
                        <td class="invoice-a4-num">{{ $rp($line['subtotal']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="invoice-a4-empty">Tidak ada rincian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <section class="invoice-a4-totals">
            @if ($showSubtotals)
                <div class="invoice-a4-row">
                    <span>Barang</span>
                    <span>{{ $rp($transaction->subtotal_items) }}</span>
                </div>
                <div class="invoice-a4-row">
                    <span>Jasa</span>
                    <span>{{ $rp($transaction->subtotal_services) }}</span>
                </div>
            @endif
            @if ((float) $transaction->discount > 0)
                <div class="invoice-a4-row">
                    <span>Diskon</span>
                    <span>- {{ $rp($transaction->discount) }}</span>
                </div>
            @endif
            <div class="invoice-a4-row invoice-a4-row--total">
                <span>TOTAL</span>
                <span>{{ $rp($transaction->total) }}</span>
            </div>
            @if ($transaction->payment_method === 'cash' && $transaction->cash_received !== null)
                <div class="invoice-a4-row">
                    <span>Bayar</span>
                    <span>{{ $rp($transaction->cash_received) }}</span>
                </div>
                <div class="invoice-a4-row">
                    <span>Kembali</span>
                    <span>{{ $rp($transaction->cash_change ?? 0) }}</span>
                </div>
            @endif
        </section>

        @if ($transaction->notes)
            <p class="invoice-a4-notes"><strong>Catatan:</strong> {{ $transaction->notes }}</p>
        @endif

        <footer class="invoice-a4-footer">
            Terima kasih · Sah tanpa tanda tangan<br>
            Dicetak {{ $printedAt }}
        </footer>
    </article>
@endsection

@push('js')
    @if (request()->boolean('print'))
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endif
@endpush
