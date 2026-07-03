@php
    $submitLabel = $submitLabel ?? 'Bayar & Selesai';
    $showTechHint = $showTechHint ?? true;
    $submitDisabled = $submitDisabled ?? true;
@endphp

<div class="mb-3">
    <label class="form-label">Teknisi <span id="tech-required" class="text-danger d-none">*</span></label>
    <select name="technician_id" id="technician_id" class="form-control-clean atha-searchable-select">
        <option value="">-- Pilih Teknisi --</option>
        @foreach ($technicians as $technician)
            <option value="{{ $technician->id }}" data-commission-percent="{{ (float) $technician->commission_percent }}">{{ $technician->code }} — {{ $technician->name }} ({{ number_format((float) $technician->commission_percent, 0) }}%)</option>
        @endforeach
    </select>
    @if ($showTechHint)
        <div class="form-hint-sm">Wajib jika transaksi memiliki jasa servis.</div>
    @endif
</div>

<div class="row g-2 mb-3">
    <div class="col-md-6">
        <label class="form-label" for="discount">Diskon (Rp)</label>
        <input type="number" name="discount" id="discount" class="form-control form-control-clean" min="0" value="{{ $discountValue ?? 0 }}" step="0.01">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="payment_method">Metode Bayar <span class="text-danger">*</span></label>
        <select name="payment_method" id="payment_method" class="form-select form-control-clean" required>
            @foreach (config('workshop.payment_methods', []) as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mb-3 d-none" id="bank-account-wrap">
    <label class="form-label" for="bank_account_id">Akun Bank <span class="text-danger">*</span></label>
    <select name="bank_account_id" id="bank_account_id" class="form-select form-control-clean">
        <option value="">-- Pilih Akun Bank --</option>
        @foreach ($bankAccounts as $account)
            <option value="{{ $account->id }}">{{ $account->displayLabel() }}</option>
        @endforeach
    </select>
    <div class="form-hint-sm">Wajib untuk pembayaran transfer bank.</div>
    @can('bank account create')
        <div class="form-hint-sm mt-1">
            <a href="{{ route('bank-accounts.index') }}" target="_blank">Kelola akun bank</a>
        </div>
    @endcan
</div>

<div class="mb-3">
    <label class="form-label" for="notes">Catatan</label>
    <textarea name="notes" id="notes" class="form-control form-control-clean" rows="2">{{ $notesValue ?? '' }}</textarea>
</div>

<hr class="my-2">

<div class="row g-2 small mb-2">
    <div class="col-6 d-flex justify-content-between gap-1">
        <span class="text-muted">Sub. Barang</span>
        <span id="sum-items" class="fw-medium text-end">Rp 0</span>
    </div>
    <div class="col-6 d-flex justify-content-between gap-1">
        <span class="text-muted">Sub. Jasa</span>
        <span id="sum-services" class="fw-medium text-end">Rp 0</span>
    </div>
    <div class="col-6 d-flex justify-content-between gap-1">
        <span class="text-muted">Diskon</span>
        <span id="sum-discount" class="text-danger text-end">- Rp 0</span>
    </div>
    <div class="col-6 d-flex justify-content-between gap-1 fw-bold">
        <span>Total</span>
        <span id="sum-total" class="text-end">Rp 0</span>
    </div>
</div>

<div class="row g-2 mb-3 d-none tx-cash-payment" id="cash-payment-wrap">
    <div class="col-md-6">
        <label class="form-label" for="amount_paid">Uang Diterima (Rp) <span class="text-danger">*</span></label>
        <input type="number" id="amount_paid" class="form-control form-control-clean" min="0" step="1" placeholder="0" inputmode="numeric">
    </div>
    <div class="col-md-6">
        <label class="form-label">Kembalian</label>
        <div class="form-control form-control-clean tx-cash-change-display d-flex align-items-center justify-content-between">
            <span class="text-muted small d-none d-md-inline">Uang kembali</span>
            <span id="cash-change" class="fw-bold text-success ms-auto">Rp 0</span>
        </div>
    </div>
</div>

<div class="alert alert-light border py-2 px-3 small mb-3 atha-summary-box">
    <div class="fw-semibold mb-2"><i class="bi bi-percent me-1"></i> Pembagian komisi</div>
    <div class="row g-2 small">
        <div class="col-6 d-flex justify-content-between gap-1">
            <span>Komisi (<span id="label-tech-percent">{{ (int) config('workshop.default_technician_commission_percent', 20) }}</span>%)</span>
            <span id="sum-tech-commission" class="fw-medium text-end">Rp 0</span>
        </div>
        <div class="col-6 d-flex justify-content-between gap-1">
            <span>Owner jasa (<span id="label-owner-percent">{{ 100 - (int) config('workshop.default_technician_commission_percent', 20) }}</span>%)</span>
            <span id="sum-owner-service" class="text-end">Rp 0</span>
        </div>
        <div class="col-6 d-flex justify-content-between gap-1">
            <span>Sparepart</span>
            <span id="sum-owner-items" class="text-end">Rp 0</span>
        </div>
        <div class="col-6 d-flex justify-content-between gap-1 fw-semibold">
            <span>Total owner</span>
            <span id="sum-owner-total" class="text-end">Rp 0</span>
        </div>
    </div>
</div>

<div class="d-grid gap-2">
    <button type="submit" class="btn btn-primary btn-lg" id="btn-submit" @if($submitDisabled) disabled @endif>
        <i class="bi bi-check-lg"></i> {{ $submitLabel }}
    </button>
    <a href="{{ route('transactions.index') }}" class="btn btn-light-action">Kembali</a>
</div>
