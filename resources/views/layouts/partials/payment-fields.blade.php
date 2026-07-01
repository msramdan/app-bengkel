@php
    $paymentMethods = $paymentMethods ?? config('workshop.payment_methods', []);
@endphp

<div class="mb-3">
    <label class="form-label">Metode Bayar <span class="text-danger">*</span></label>
    <select name="payment_method" id="payment_method" class="form-select form-control-clean" required>
        @foreach ($paymentMethods as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3 d-none" id="bank-account-wrap">
    <label class="form-label">Akun Bank <span class="text-danger">*</span></label>
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
