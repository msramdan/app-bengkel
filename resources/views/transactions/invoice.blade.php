@extends('layouts.print')

@section('title', 'Nota '.$transaction->transaction_no)

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

    <article class="receipt-thermal">
        @if (brand_has_custom_logo())
            <div class="receipt-center receipt-logo-wrap">
                <img src="{{ brand_logo_url() }}" alt="{{ brand_name() }}" class="receipt-logo">
            </div>
        @endif
        <div class="receipt-center receipt-brand">{{ brand_name() }}</div>
        @if (brand_address() !== '')
            <div class="receipt-center receipt-meta">{{ brand_address() }}</div>
        @endif
        @if (brand_whatsapp() !== '')
            <div class="receipt-center receipt-meta">WA: {{ brand_whatsapp() }}</div>
        @endif
        <div class="receipt-center receipt-title">NOTA PENJUALAN</div>
        <div class="receipt-center receipt-meta">{{ $transaction->transaction_no }} · {{ $txDate }}</div>

        <div class="receipt-sep"></div>

        <div class="receipt-text receipt-text--strong">{{ $transaction->displayCustomerName() }}</div>
        @if ($customerMeta !== '')
            <div class="receipt-text receipt-text--muted">{{ $customerMeta }}</div>
        @endif
        <div class="receipt-row receipt-row--tight">
            <span>{{ $paymentLabel }}</span>
            @if ($transaction->technician)
                <span>{{ $transaction->technician->name }}</span>
            @endif
        </div>

        <div class="receipt-sep"></div>

        @forelse ($lines as $line)
            <div class="receipt-item">
                <div class="receipt-row receipt-row--tight">
                    <span class="receipt-item-name">{{ $line['name'] }}</span>
                    <span>{{ $rp($line['subtotal']) }}</span>
                </div>
                <div class="receipt-text receipt-text--muted">{{ number_format($line['quantity']) }} × {{ $rp($line['unit_price']) }}</div>
            </div>
        @empty
            <div class="receipt-text receipt-center">Tidak ada rincian.</div>
        @endforelse

        <div class="receipt-sep"></div>

        @if ($showSubtotals)
            <div class="receipt-row receipt-row--tight">
                <span>Barang</span>
                <span>{{ $rp($transaction->subtotal_items) }}</span>
            </div>
            <div class="receipt-row receipt-row--tight">
                <span>Jasa</span>
                <span>{{ $rp($transaction->subtotal_services) }}</span>
            </div>
        @endif
        @if ((float) $transaction->discount > 0)
            <div class="receipt-row receipt-row--tight">
                <span>Diskon</span>
                <span>- {{ $rp($transaction->discount) }}</span>
            </div>
        @endif
        <div class="receipt-row receipt-row--total">
            <span>TOTAL</span>
            <span>{{ $rp($transaction->total) }}</span>
        </div>

        @if ($transaction->payment_method === 'cash' && $transaction->cash_received !== null)
            <div class="receipt-row receipt-row--tight">
                <span>Bayar</span>
                <span>{{ $rp($transaction->cash_received) }}</span>
            </div>
            <div class="receipt-row receipt-row--tight">
                <span>Kembali</span>
                <span>{{ $rp($transaction->cash_change ?? 0) }}</span>
            </div>
        @endif

        <div class="receipt-center receipt-status">LUNAS</div>

        @if ($transaction->notes)
            <div class="receipt-sep"></div>
            <div class="receipt-text receipt-text--muted"><strong>Cat:</strong> {{ $transaction->notes }}</div>
        @endif

        <div class="receipt-sep"></div>
        <div class="receipt-center receipt-footer">Terima kasih · Sah tanpa tanda tangan</div>
        <div class="receipt-center receipt-text--muted">{{ $printedAt }}</div>
    </article>
@endsection

@push('js')
    @if (request()->boolean('print'))
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endif
@endpush
