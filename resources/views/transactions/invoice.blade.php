@extends('layouts.print')

@section('title', 'Nota '.$transaction->transaction_no)

@section('content')
    @php
        $paymentLabel = \App\Support\PaymentMethodResolver::label($transaction->payment_method);
        if ($transaction->payment_method === 'transfer' && $transaction->bankAccount) {
            $paymentLabel .= ' — '.$transaction->bankAccount->displayLabel();
        }
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
        $printedAt = now()->timezone(config('app.timezone'))->format('d/m/Y, H.i.s');
        $txDate = $transaction->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');

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

    <article class="receipt-thermal">
        <div class="receipt-center receipt-brand">{{ brand_name() }}</div>
        <div class="receipt-center receipt-sub">{{ brand_tagline() }}</div>

        <div class="receipt-divider">--------------------------</div>
        <div class="receipt-center receipt-title">NOTA PENJUALAN</div>
        <div class="receipt-divider">--------------------------</div>

        <div class="receipt-row">
            <span>No.</span>
            <span>{{ $transaction->transaction_no }}</span>
        </div>
        <div class="receipt-row">
            <span>Tgl</span>
            <span>{{ $txDate }}</span>
        </div>

        <div class="receipt-divider">--------------------------</div>
        <div class="receipt-label">Kepada:</div>
        <div class="receipt-text">{{ $transaction->displayCustomerName() }}</div>
        @if ($transaction->customer?->phone)
            <div class="receipt-text">{{ $transaction->customer->phone }}</div>
        @endif
        @if ($transaction->customer?->address)
            <div class="receipt-text">{{ $transaction->customer->address }}</div>
        @endif
        <div class="receipt-row">
            <span>Metode Bayar</span>
            <span>{{ $paymentLabel }}</span>
        </div>
        @if ($transaction->technician)
            <div class="receipt-row">
                <span>Teknisi</span>
                <span>{{ $transaction->technician->name }}</span>
            </div>
        @endif

        <div class="receipt-divider">--------------------------</div>

        @forelse ($lines as $line)
            <div class="receipt-item">
                <div class="receipt-item-name">{{ $line['name'] }}</div>
                <div class="receipt-row receipt-item-meta">
                    <span>{{ number_format($line['quantity']) }} x {{ $rp($line['unit_price']) }}</span>
                    <span>{{ $rp($line['subtotal']) }}</span>
                </div>
            </div>
        @empty
            <div class="receipt-text receipt-center">Tidak ada rincian.</div>
        @endforelse

        <div class="receipt-divider">--------------------------</div>

        @if ((float) $transaction->subtotal_items > 0)
            <div class="receipt-row">
                <span>Subtotal Barang</span>
                <span>{{ $rp($transaction->subtotal_items) }}</span>
            </div>
        @endif
        @if ((float) $transaction->subtotal_services > 0)
            <div class="receipt-row">
                <span>Subtotal Jasa</span>
                <span>{{ $rp($transaction->subtotal_services) }}</span>
            </div>
        @endif
        @if ((float) $transaction->discount > 0)
            <div class="receipt-row">
                <span>Diskon</span>
                <span>- {{ $rp($transaction->discount) }}</span>
            </div>
        @endif
        <div class="receipt-row receipt-row--bold">
            <span>TOTAL BAYAR</span>
            <span>{{ $rp($transaction->total) }}</span>
        </div>

        @if ($transaction->payment_method === 'cash' && $transaction->cash_received !== null)
            <div class="receipt-row">
                <span>Bayar</span>
                <span>{{ $rp($transaction->cash_received) }}</span>
            </div>
            <div class="receipt-row receipt-row--bold">
                <span>Kembalian</span>
                <span>{{ $rp($transaction->cash_change ?? 0) }}</span>
            </div>
        @endif

        <div class="receipt-divider">--------------------------</div>
        <div class="receipt-center receipt-status">LUNAS</div>
        <div class="receipt-divider">--------------------------</div>

        @if ($transaction->notes)
            <div class="receipt-label">Catatan:</div>
            <div class="receipt-text">{{ $transaction->notes }}</div>
            <div class="receipt-divider">--------------------------</div>
        @endif

        <div class="receipt-center receipt-thanks">Terima kasih telah berkunjung.</div>
        <div class="receipt-center receipt-muted">Sah tanpa tanda tangan.</div>
        <div class="receipt-center receipt-muted">{{ $printedAt }}</div>
    </article>
@endsection

@push('js')
    @if (request()->boolean('print'))
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endif
@endpush
