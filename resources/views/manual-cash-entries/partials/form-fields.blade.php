@php
    $isIncome = $type === 'income';
    $title = $isIncome ? 'Pemasukan Manual' : 'Pengeluaran Manual';
    $subtitle = $isIncome
        ? 'Catat pemasukan kas di luar transaksi penjualan (mis. jual barang bekas, scrap besi).'
        : 'Catat pengeluaran operasional (mis. gaji karyawan, listrik, sewa).';
    $createPermission = $isIncome ? 'manual income create' : 'manual expense create';
    $cancelPermission = $isIncome ? 'manual income cancel' : 'manual expense cancel';
    $paymentMethods = $isIncome
        ? config('workshop.payment_methods', [])
        : config('workshop.purchase_payment_methods', []);
@endphp

<div class="mb-3">
    <label class="form-label">Kategori <span class="text-danger">*</span></label>
    <select name="category_id" class="form-select form-control-clean" required>
        <option value="">-- Pilih Kategori --</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </select>
</div>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
        <input type="number" name="amount" class="form-control form-control-clean" min="0.01" step="0.01" required placeholder="0">
    </div>
    <div class="col-md-6">
        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
        <input type="datetime-local" name="occurred_at" class="form-control form-control-clean" required value="{{ now()->format('Y-m-d\TH:i') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Metode Bayar <span class="text-danger">*</span></label>
        <select name="payment_method" id="entry_payment_method" class="form-select form-control-clean" required>
            @foreach ($paymentMethods as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 d-none" id="entry-bank-account-wrap">
        <label class="form-label">Akun Bank <span class="text-danger">*</span></label>
        <select name="bank_account_id" id="entry_bank_account_id" class="form-select form-control-clean">
            <option value="">-- Pilih Akun Bank --</option>
            @foreach ($bankAccounts as $account)
                <option value="{{ $account->id }}">{{ $account->displayLabel() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Keterangan</label>
        <textarea name="description" class="form-control form-control-clean" rows="2" placeholder="Contoh: {{ $isIncome ? 'Penjualan besi tua ke pengepul' : 'Pembayaran listrik bulan ini' }}"></textarea>
    </div>
</div>
