@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
@endphp

<section class="item-insights" aria-label="Ringkasan stok dan barang laris">
    <article class="item-insight-value">
        <div class="item-insight-value__top">
            <div class="item-insight-value__icon" aria-hidden="true">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div>
                <p class="item-insight-kicker">Modal di rak</p>
                <h2 class="item-insight-title">Nilai Stok Bengkel</h2>
            </div>
        </div>
        <p class="item-insight-amount">{{ $rp($stockInsight['stock_value']) }}</p>
        <p class="item-insight-formula">Stok real × harga beli</p>
        <div class="item-insight-chips">
            <span>{{ number_format($stockInsight['sku_count']) }} jenis barang</span>
            <span>{{ number_format($stockInsight['sku_in_stock']) }} masih ada stok</span>
            <span>{{ number_format($stockInsight['unit_count']) }} unit di rak</span>
        </div>
    </article>

    <article class="item-insight-sellers">
        <div class="item-insight-sellers__head">
            <div class="item-insight-sellers__icon" aria-hidden="true">
                <i class="bi bi-fire"></i>
            </div>
            <div class="flex-grow-1">
                <p class="item-insight-kicker item-insight-kicker--muted">Penjualan</p>
                <h2 class="item-insight-title item-insight-title--dark">Barang Paling Laris</h2>
            </div>
        </div>

        @if ($bestSellers->isEmpty())
            <div class="item-insight-empty">
                <i class="bi bi-bag-x"></i>
                <p>Belum ada penjualan barang.</p>
            </div>
        @else
            <p class="item-insight-hint">Klik nama barang untuk lihat detail</p>
            <ol class="item-seller-list">
                @foreach ($bestSellers as $index => $row)
                    <li class="item-seller-item">
                        <button type="button" class="item-seller-toggle" aria-expanded="false">
                            <span class="item-seller-rank item-seller-rank--{{ min($index + 1, 3) }}">{{ $index + 1 }}</span>
                            <span class="item-seller-name">{{ $row->item_name }}</span>
                            <span class="item-seller-qty">{{ number_format($row->qty_sold) }}x</span>
                            <i class="bi bi-chevron-down item-seller-caret" aria-hidden="true"></i>
                        </button>
                        <div class="item-seller-detail" hidden>
                            @if ($row->photo_url)
                                <img src="{{ $row->photo_url }}" alt="" class="item-seller-photo">
                            @else
                                <span class="item-seller-photo item-seller-photo--empty"><i class="bi bi-box-seam"></i></span>
                            @endif
                            <div>
                                <div class="item-seller-detail-line"><span>Kode</span><strong>{{ $row->item_code ?: '—' }}</strong></div>
                                <div class="item-seller-detail-line"><span>Stok sekarang</span><strong>{{ number_format($row->stock) }}</strong></div>
                                <div class="item-seller-detail-line"><span>Omzet</span><strong>{{ $rp($row->revenue) }}</strong></div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </article>
</section>
