@extends('layouts.app')

@section('title', 'Edit Transaksi')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Transaksi Penjualan', 'url' => route('transactions.index')],
            ['label' => 'Edit'],
        ],
        'title' => 'Edit Transaksi',
        'subtitle' => $transaction->transaction_no,
    ])

    <form id="transaction-form">
        @csrf
        @method('PUT')

        <div class="data-panel mb-4">
            <div class="data-panel-head">
                <h2 class="data-panel-title"><i class="bi bi-info-circle me-1"></i> Informasi Transaksi</h2>
            </div>
            <div class="data-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">No. Transaksi</div>
                        <div class="fw-semibold">{{ $transaction->transaction_no }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Pelanggan</div>
                        <div class="fw-semibold">{{ $transaction->displayCustomerName() }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Waktu</div>
                        <div class="fw-semibold">{{ $transaction->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <div class="form-hint-sm mt-2 mb-0">Pelanggan tidak dapat diubah. Koreksi stok dan laporan keuangan disesuaikan otomatis.</div>
            </div>
        </div>

        <div class="row g-4 align-items-lg-start">
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
                                                data-price="{{ $item->selling_price }}"
                                                data-member-price="{{ $item->member_price ?? 0 }}">
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
                            <div id="items-cart-empty" class="text-center text-muted py-3 small d-none">Belum ada barang.</div>
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
                            <div id="services-cart-empty" class="text-center text-muted py-3 small d-none">Belum ada jasa servis.</div>
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
                        <div class="mb-3">
                            <label class="form-label">Teknisi <span id="tech-required" class="text-danger d-none">*</span></label>
                            <select name="technician_id" id="technician_id" class="form-control-clean atha-searchable-select">
                                <option value="">-- Pilih Teknisi --</option>
                                @foreach ($technicians as $technician)
                                    <option value="{{ $technician->id }}" data-commission-percent="{{ (float) $technician->commission_percent }}">{{ $technician->code }} — {{ $technician->name }} ({{ number_format((float) $technician->commission_percent, 0) }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diskon (Rp)</label>
                            <input type="number" name="discount" id="discount" class="form-control form-control-clean" min="0" step="0.01">
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

                        <div class="mb-3 d-none tx-cash-payment" id="cash-payment-wrap">
                            <label class="form-label" for="amount_paid">Uang Diterima (Rp) <span class="text-danger">*</span></label>
                            <input type="number" id="amount_paid" class="form-control form-control-clean" min="0" step="1" placeholder="0" inputmode="numeric">
                            <div class="d-flex justify-content-between align-items-center mt-3 py-2 px-3 rounded tx-cash-change-row">
                                <span class="text-muted">Kembalian</span>
                                <span id="cash-change" class="fw-bold fs-5 text-success">Rp 0</span>
                            </div>
                        </div>

                        <div class="alert alert-light border py-2 px-3 small mb-3 atha-summary-box">
                            <div class="fw-semibold mb-1"><i class="bi bi-percent me-1"></i> Pembagian komisi</div>
                            <div class="d-flex justify-content-between">
                                <span>Komisi teknisi (<span id="label-tech-percent">0</span>%)</span>
                                <span id="sum-tech-commission" class="fw-medium">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Bagian owner jasa (<span id="label-owner-percent">0</span>%)</span>
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
                            <button type="submit" class="btn btn-primary btn-lg" id="btn-submit">
                                <i class="bi bi-check-lg"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-light-action">Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="{{ asset('js/payment-fields.js') }}"></script>
    <script src="{{ asset('js/transaction-edit.js') }}"></script>
    <script>
        AthaPaymentFields.init();
        AthaTransactionEdit.init({
            updateUrl: '{{ route('transactions.update', $transaction) }}',
            redirectUrl: '{{ route('transactions.index') }}',
            itemAvailabilityUrl: '{{ route('transactions.items.availability') }}',
            defaultTechPercent: {{ (int) config('workshop.default_technician_commission_percent', 20) }},
            stockCredit: @json($stockCredit),
            usesMemberPricing: @json((bool) $transaction->customer?->is_member),
            initial: {
                technician_id: @json($transaction->technician_id),
                discount: {{ (float) $transaction->discount }},
                notes: @json($transaction->notes),
                payment_method: @json($transaction->payment_method),
                bank_account_id: @json($transaction->bank_account_id),
                amount_paid: @json($transaction->cash_received),
                items: @json($transaction->items->map(fn ($line) => [
                    'item_id' => $line->item_id,
                    'code' => $line->item_code,
                    'name' => $line->item_name,
                    'quantity' => (int) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                ])),
                services: @json($transaction->serviceLines->map(fn ($line) => [
                    'workshop_service_id' => $line->workshop_service_id,
                    'code' => $line->service_code,
                    'name' => $line->service_name,
                    'quantity' => (int) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                ])),
            },
            items: @json($items),
            services: @json($services),
        });
    </script>
@endpush
