@extends('layouts.app')

@section('title', 'Transaksi Baru')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Transaksi Penjualan', 'url' => route('transactions.index')],
            ['label' => 'Baru'],
        ],
        'title' => 'Transaksi Baru',
        'subtitle' => 'Penjualan sparepart, servis jasa, atau gabungan keduanya.',
    ])

    <form id="transaction-form">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="data-panel mb-4">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-box-seam me-1"></i> Barang / Sparepart</h2>
                    </div>
                    <div class="data-panel-body">
                        <div class="stock-cart-add border rounded p-3 mb-3">
                            <div class="cart-add-row row g-2">
                                <div class="col-md-7">
                                    <label class="form-label small mb-0" for="item_select">Pilih Barang</label>
                                    <select id="item_select" class="form-control-clean cart-add-control atha-searchable-select">
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}"
                                                data-code="{{ $item->code }}"
                                                data-name="{{ $item->name }}"
                                                data-stock="{{ $item->stock }}"
                                                data-price="{{ $item->selling_price }}">
                                                {{ $item->code }} — {{ $item->name }} (Stok: {{ number_format($item->stock) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0" for="item_qty">Qty</label>
                                    <input type="number" id="item_qty" class="form-control form-control-clean cart-add-control" min="1" placeholder="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0 cart-add-action-label" aria-hidden="true">Aksi</label>
                                    <button type="button" class="btn btn-outline-primary w-100 cart-add-btn" id="btn-add-item">
                                        <i class="bi bi-cart-plus"></i>
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
                                        <th class="text-end">Harga</th>
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

                <div class="data-panel">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-wrench-adjustable me-1"></i> Jasa Servis</h2>
                    </div>
                    <div class="data-panel-body">
                        <div class="stock-cart-add border rounded p-3 mb-3">
                            <div class="cart-add-row row g-2">
                                <div class="col-md-7">
                                    <label class="form-label small mb-0" for="service_select">Pilih Jasa</label>
                                    <select id="service_select" class="form-control-clean cart-add-control atha-searchable-select">
                                        <option value="">-- Pilih Jasa --</option>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->id }}"
                                                data-code="{{ $service->code }}"
                                                data-name="{{ $service->name }}"
                                                data-price="{{ $service->price }}">
                                                {{ $service->code }} — {{ $service->name }} (Rp {{ number_format($service->price, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0" for="service_qty">Qty</label>
                                    <input type="number" id="service_qty" class="form-control form-control-clean cart-add-control" min="1" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0 cart-add-action-label" aria-hidden="true">Aksi</label>
                                    <button type="button" class="btn btn-outline-primary w-100 cart-add-btn" id="btn-add-service">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jasa</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="services-cart-body"></tbody>
                            </table>
                            <div id="services-cart-empty" class="text-center text-muted py-3 small">Belum ada jasa servis.</div>
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
                            <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                            <select id="customer_id" class="form-control-clean atha-searchable-select">
                                <option value="">-- Pilih Pelanggan --</option>
                                <option value="__umum__">{{ config('workshop.walk_in_customer_label', 'Umum') }} (pelanggan lewat)</option>
                                <optgroup label="Pelanggan terdaftar">
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->code }} — {{ $customer->name }}</option>
                                    @endforeach
                                </optgroup>
                                <option value="__new__">+ Pelanggan baru...</option>
                            </select>
                            <div class="form-hint-sm">Pilih <strong>Umum</strong> untuk pembeli lewat tanpa simpan ke master data.</div>
                        </div>
                        <div id="new-customer-fields" class="border rounded p-3 mb-3 d-none customer-inline-panel">
                            <div class="small fw-semibold mb-2"><i class="bi bi-person-plus me-1"></i> Data pelanggan baru</div>
                            <div class="mb-2">
                                <label class="form-label small mb-0" for="new_customer_name">Nama <span class="text-danger">*</span></label>
                                <input type="text" id="new_customer_name" class="form-control form-control-clean" placeholder="Nama pelanggan" autocomplete="off">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-0" for="new_customer_phone">Telepon</label>
                                <input type="text" id="new_customer_phone" class="form-control form-control-clean" placeholder="Opsional" autocomplete="off">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small mb-0" for="new_customer_address">Alamat</label>
                                <input type="text" id="new_customer_address" class="form-control form-control-clean" placeholder="Opsional" autocomplete="off">
                            </div>
                            <div class="form-hint-sm mt-2 mb-0">Otomatis tersimpan ke master pelanggan saat transaksi disimpan.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teknisi <span id="tech-required" class="text-danger d-none">*</span></label>
                            <select name="technician_id" id="technician_id" class="form-control-clean atha-searchable-select">
                                <option value="">-- Pilih Teknisi --</option>
                                @foreach ($technicians as $technician)
                                    <option value="{{ $technician->id }}" data-commission-percent="{{ (float) $technician->commission_percent }}">{{ $technician->code }} — {{ $technician->name }} ({{ number_format((float) $technician->commission_percent, 0) }}%)</option>
                                @endforeach
                            </select>
                            <div class="form-hint-sm">Wajib jika transaksi memiliki jasa servis.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diskon (Rp)</label>
                            <input type="number" name="discount" id="discount" class="form-control form-control-clean" min="0" value="0" step="0.01">
                        </div>

                        @include('layouts.partials.payment-fields', ['bankAccounts' => $bankAccounts])

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" id="notes" class="form-control form-control-clean" rows="2"></textarea>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal Barang</span>
                            <span id="sum-items">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal Jasa</span>
                            <span id="sum-services">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Diskon</span>
                            <span id="sum-discount" class="text-danger">- Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                            <span>Total</span>
                            <span id="sum-total">Rp 0</span>
                        </div>

                        <div class="alert alert-light border py-2 px-3 small mb-3">
                            <div class="fw-semibold mb-1"><i class="bi bi-percent me-1"></i> Pembagian (sesuai % komisi teknisi)</div>
                            <div class="d-flex justify-content-between">
                                <span>Komisi teknisi (<span id="label-tech-percent">{{ (int) config('workshop.default_technician_commission_percent', 20) }}</span>% jasa)</span>
                                <span id="sum-tech-commission" class="fw-medium">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Bagian owner jasa (<span id="label-owner-percent">{{ 100 - (int) config('workshop.default_technician_commission_percent', 20) }}</span>%)</span>
                                <span id="sum-owner-service">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Sparepart (100% toko)</span>
                                <span id="sum-owner-items">Rp 0</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-semibold">
                                <span>Total bagian owner</span>
                                <span id="sum-owner-total">Rp 0</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="btn-submit" disabled>
                                <i class="bi bi-check-lg"></i> Simpan Transaksi
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="{{ asset('js/payment-fields.js') }}"></script>
    <script src="{{ asset('js/transaction-cart.js') }}"></script>
    <script>
        AthaPaymentFields.init();
        AthaTransactionCart.init({
            storeUrl: '{{ route('transactions.store') }}',
            redirectUrl: '{{ route('transactions.index') }}',
            techPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            ownerPercent: {{ 100 - (int) config('workshop.default_technician_commission_percent', 20) }},
            defaultTechPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            items: @json($items),
            services: @json($services),
        });
    </script>
@endpush
