@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $topSeller = $bestSellers->first();
@endphp

<section class="row g-3 mb-3" aria-label="Ringkasan stok dan barang laris">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="dash-kpi dash-kpi-primary">
            <div class="dash-kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="dash-kpi-body">
                <div class="dash-kpi-label">Nilai Stok Bengkel</div>
                <div class="dash-kpi-value">{{ $rp($stockInsight['stock_value']) }}</div>
                <div class="dash-kpi-meta">Stok real × harga beli</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="stat-card dash-mini-stat">
            <div class="dash-mini-icon text-primary"><i class="bi bi-box-seam"></i></div>
            <div class="stat-label">Jenis Barang</div>
            <div class="stat-value accent">{{ number_format($stockInsight['sku_count']) }}</div>
            <div class="stat-meta">{{ number_format($stockInsight['sku_in_stock']) }} masih ada stok</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="stat-card dash-mini-stat">
            <div class="dash-mini-icon text-info"><i class="bi bi-layers"></i></div>
            <div class="stat-label">Unit di Rak</div>
            <div class="stat-value accent">{{ number_format($stockInsight['unit_count']) }}</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <button type="button"
            class="item-insight-trigger"
            data-bs-toggle="modal"
            data-bs-target="#item-best-sellers-modal"
            title="Lihat daftar barang laris">
            <div class="dash-kpi dash-kpi-warning">
                <div class="dash-kpi-icon"><i class="bi bi-fire"></i></div>
                <div class="dash-kpi-body">
                    <div class="dash-kpi-label">Barang Paling Laris</div>
                    <div class="dash-kpi-value item-insight-top-name">
                        {{ $topSeller?->item_name ?? 'Belum ada penjualan' }}
                    </div>
                    <div class="dash-kpi-meta">
                        @if ($topSeller)
                            {{ number_format($topSeller->qty_sold) }}x terjual · klik untuk daftar
                        @else
                            Klik untuk melihat daftar
                        @endif
                    </div>
                </div>
            </div>
        </button>
    </div>
</section>

<div class="modal fade" id="item-best-sellers-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-clean">
            <div class="modal-header">
                <h5 class="modal-title">Barang Paling Laris</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                @if ($bestSellers->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">Belum ada penjualan barang.</p>
                @else
                    <ol class="item-seller-modal-list">
                        @foreach ($bestSellers as $index => $row)
                            <li>
                                <span class="item-seller-rank item-seller-rank--{{ min($index + 1, 3) }}">{{ $index + 1 }}</span>
                                @if ($row->photo_url)
                                    <img src="{{ $row->photo_url }}" alt="" class="item-seller-photo">
                                @else
                                    <span class="item-seller-photo item-seller-photo--empty"><i class="bi bi-box-seam"></i></span>
                                @endif
                                <div class="item-seller-modal-meta">
                                    <div class="item-seller-modal-name">{{ $row->item_name }}</div>
                                    <div class="item-seller-modal-sub">{{ $row->item_code ?: '—' }} · stok {{ number_format($row->stock) }}</div>
                                </div>
                                <div class="item-seller-modal-stats">
                                    <strong>{{ number_format($row->qty_sold) }}x</strong>
                                    <span>{{ $rp($row->revenue) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </div>
</div>
