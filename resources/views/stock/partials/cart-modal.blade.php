<div class="modal fade" data-bs-backdrop="static" id="form-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content modal-content-clean">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="stock-cart-add border rounded p-3 mb-3">
                    <div class="fw-semibold mb-2"><i class="bi bi-plus-circle me-1"></i> Tambah Barang</div>
                    <form id="stock-add-form" class="cart-add-row row g-2">
                        <div class="col-md-7">
                            <label class="form-label small mb-0" for="cart_item_id">Barang</label>
                            <select id="cart_item_id" class="form-select form-control-clean cart-add-control">
                                <option value="">-- Pilih Barang --</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->code }} — {{ $item->name }} (Stok: {{ number_format($item->stock) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0" for="cart_quantity">Jumlah</label>
                            <input type="number" id="cart_quantity" class="form-control form-control-clean cart-add-control" min="1" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0 cart-add-action-label" aria-hidden="true">Aksi</label>
                            <button type="submit" class="btn btn-outline-primary w-100 cart-add-btn">
                                <i class="bi bi-cart-plus"></i>
                            </button>
                        </div>
                        <div class="col-12">
                            <div class="cart-add-hint" id="stock-hint"></div>
                        </div>
                    </form>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-semibold"><i class="bi bi-cart3 me-1"></i> Keranjang (<span id="stock-cart-count">0</span>)</div>
                </div>

                <div class="table-responsive border rounded mb-3">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Barang</th>
                                <th class="text-center" style="width:90px">Stok</th>
                                <th class="text-center" style="width:90px">Jumlah</th>
                                <th class="text-end" style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="stock-cart-body"></tbody>
                    </table>
                    <div id="stock-cart-empty" class="text-center text-muted py-4 small">
                        Keranjang masih kosong. Tambahkan barang di atas.
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="alert alert-light border py-2 px-3 mb-0 small">
                            <i class="bi bi-info-circle me-1"></i>
                            No. transaksi dibuat <strong>otomatis</strong> saat disimpan (contoh: <code>STM-20260701-0001</code>).
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Referensi Eksternal</label>
                        <input type="text" id="reference_no" class="form-control form-control-clean" placeholder="No. faktur supplier, opsional">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea id="notes" class="form-control form-control-clean" rows="2" placeholder="Opsional"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-submit-cart" disabled>
                    <i class="bi bi-check-lg"></i> Simpan Transaksi
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" data-bs-backdrop="static" id="show-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content modal-content-clean">
            <div class="modal-header">
                <h5 class="modal-title">Detail {{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
