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
        <div class="tx-order-tabs mb-3" id="tx-order-tabs">
            <div class="tx-order-tabs-scroll">
                <div class="tx-order-tabs-list" id="tx-order-tabs-list">
                    <button type="button" class="tx-order-tab-add" id="btn-add-tab" title="Transaksi baru">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
            <div class="form-hint-sm mt-2 mb-0">Pindah tab untuk layani pelanggan lain. Stok dicek saat bayar/submit.</div>
        </div>
        <div class="row g-4 align-items-lg-start">
            <div class="col-lg-8">
                <div class="data-panel mb-4" id="customer-panel">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-person me-1"></i> Pelanggan</h2>
                    </div>
                    <div class="data-panel-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih Pelanggan <span class="text-danger">*</span></label>
                            <select id="customer_id" class="form-control-clean atha-searchable-select atha-select2-customer">
                                <option value="">-- Pilih Pelanggan --</option>
                                <option value="__umum__" data-is-member="0">{{ config('workshop.walk_in_customer_label', 'Umum') }} (pelanggan lewat)</option>
                                <optgroup label="Pelanggan terdaftar">
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" data-is-member="{{ $customer->is_member ? '1' : '0' }}">
                                            {{ $customer->code }} — {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                <option value="__new__" data-is-member="0">+ Pelanggan baru...</option>
                            </select>
                            <div class="form-hint-sm">Pilih pelanggan terlebih dahulu sebelum menambah barang. Harga member hanya berlaku untuk barang.</div>
                        </div>
                        <div id="customer-type-remark" class="mb-3 d-none">
                            <span class="text-muted small me-1">Tipe:</span>
                            <span id="customer-type-badge" class="badge bg-secondary-subtle text-secondary">Pelanggan Biasa</span>
                        </div>
                        <div id="new-customer-fields" class="border rounded p-3 d-none customer-inline-panel">
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
                            <div class="form-hint-sm mt-2 mb-0">Disimpan sebagai pelanggan biasa (bukan member) saat transaksi disimpan.</div>
                        </div>
                    </div>
                </div>

                <div class="data-panel mb-4" id="items-section">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-box-seam me-1"></i> Barang / Sparepart</h2>
                    </div>
                    <div class="data-panel-body">
                        <div id="items-section-lock" class="items-section-empty text-center text-muted py-4 px-3">
                            <i class="bi bi-person-check fs-4 d-block mb-2"></i>
                            <div class="fw-medium">Pilih pelanggan terlebih dahulu</div>
                            <div class="small">Barang hanya bisa ditambahkan setelah pelanggan dipilih.</div>
                        </div>
                        <div id="items-add-form" class="stock-cart-add border rounded p-3 mb-3 d-none">
                            <div class="cart-add-row row g-2">
                                <div class="col-md-7">
                                    <label class="form-label small mb-0" for="item_select">Pilih Barang</label>
                                    <select id="item_select" class="form-control-clean cart-add-control atha-searchable-select atha-select2-item" disabled>
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach ($items as $item)
                                            @include('transactions.include.item-option', ['item' => $item])
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0" for="item_qty">Qty</label>
                                    <input type="number" id="item_qty" class="form-control form-control-clean cart-add-control" min="1" placeholder="0" disabled>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0 cart-add-action-label" aria-hidden="true">Aksi</label>
                                    <button type="button" class="btn btn-outline-primary w-100 cart-add-btn" id="btn-add-item" disabled>
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
                <div class="data-panel tx-summary-sticky">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-receipt me-1"></i> Ringkasan</h2>
                    </div>
                    <div class="data-panel-body">
                        @include('transactions.partials.summary-fields', [
                            'technicians' => $technicians,
                            'bankAccounts' => $bankAccounts,
                            'submitLabel' => 'Bayar & Selesai',
                            'submitDisabled' => true,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="{{ asset('js/payment-fields.js') }}"></script>
    <script src="{{ asset('js/transaction-cart.js') }}?v={{ filemtime(public_path('js/transaction-cart.js')) }}"></script>
    <script>
        AthaPaymentFields.init();
        AthaTransactionCart.init({
            storeUrl: '{{ route('transactions.store') }}',
            itemAvailabilityUrl: '{{ route('transactions.items.availability') }}',
            showUrlTemplate: '{{ route('transactions.show', '__ID__') }}',
            invoiceUrl: '{{ route('transactions.invoice', '__ID__') }}',
            redirectUrl: '{{ route('transactions.index') }}',
            techPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            ownerPercent: {{ 100 - (int) config('workshop.default_technician_commission_percent', 20) }},
            defaultTechPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            items: @json($items),
            services: @json($services),
        });
    </script>
@endpush
