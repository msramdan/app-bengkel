@extends('layouts.app')

@section('title', 'Pembelian Baru')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Pembelian', 'url' => route('purchases.index')],
            ['label' => 'Baru'],
        ],
        'title' => 'Pembelian Baru',
        'subtitle' => 'Beli barang dari supplier — stok otomatis masuk & tercatat sebagai pengeluaran.',
    ])

    <form id="purchase-form">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="data-panel">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-cart-plus me-1"></i> Barang Dibeli</h2>
                    </div>
                    <div class="data-panel-body">
                        <div class="stock-cart-add border rounded p-3 mb-3">
                            <div class="cart-add-row row g-2">
                                <div class="col-md-8">
                                    <label class="form-label small mb-0" for="item_select">Pilih Barang</label>
                                    <select id="item_select" class="form-control-clean cart-add-control atha-searchable-select">
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}"
                                                data-code="{{ $item->code }}"
                                                data-name="{{ $item->name }}"
                                                data-stock="{{ $item->stock }}"
                                                data-price="{{ $item->purchase_price }}">
                                                {{ $item->code }} — {{ $item->name }} (Stok: {{ number_format($item->stock) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0" for="item_qty">Qty</label>
                                    <input type="number" id="item_qty" class="form-control form-control-clean cart-add-control" min="1" placeholder="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0 cart-add-action-label" aria-hidden="true">Aksi</label>
                                    <button type="button" class="btn btn-outline-primary w-100 cart-add-btn" id="btn-add-item">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="col-12">
                                    <div class="cart-add-hint" id="item-hint"></div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Barang</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Harga Beli</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="items-cart-body"></tbody>
                            </table>
                            <div id="items-cart-empty" class="text-center text-muted py-3 small">Belum ada barang.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="data-panel sticky-top" style="top: 1rem;">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-receipt me-1"></i> Ringkasan</h2>
                    </div>
                    <div class="data-panel-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Supplier</label>
                            <input type="text" name="supplier_name" id="supplier_name" class="form-control form-control-clean" placeholder="Opsional">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diskon (Rp)</label>
                            <input type="number" name="discount" id="discount" class="form-control form-control-clean" min="0" value="0" step="0.01">
                        </div>

                        @include('layouts.partials.payment-fields', [
                            'bankAccounts' => $bankAccounts,
                            'paymentMethods' => config('workshop.purchase_payment_methods', []),
                        ])

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" id="notes" class="form-control form-control-clean" rows="2"></textarea>
                        </div>

                        <div class="alert alert-light border py-2 px-3 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Harga beli diambil dari <strong>master barang</strong>. Setelah simpan:
                            <ul class="mb-0 ps-3 mt-1">
                                <li>Stok masuk otomatis (lihat Stok Masuk)</li>
                                <li>Pengeluaran tercatat di Laporan Keuangan</li>
                            </ul>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span id="sum-subtotal">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Diskon</span>
                            <span id="sum-discount" class="text-danger">- Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3 text-warning">
                            <span>Total Pengeluaran</span>
                            <span id="sum-total">Rp 0</span>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="btn-submit" disabled>
                                <i class="bi bi-check-lg"></i> Simpan Pembelian
                            </button>
                            <a href="{{ route('purchases.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="{{ asset('js/payment-fields.js') }}"></script>
    <script src="{{ asset('js/purchase-cart.js') }}"></script>
    <script>
        AthaPaymentFields.init();
        AthaPurchaseCart.init({
            storeUrl: '{{ route('purchases.store') }}',
            redirectUrl: '{{ route('purchases.index') }}',
            items: @json($items),
        });
    </script>
@endpush
