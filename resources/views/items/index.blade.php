@extends('layouts.app')

@section('title', 'Data Barang')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Data Barang'],
        ],
        'title' => 'Data Barang',
        'subtitle' => 'Kelola master barang dan stok awal.',
    ])

    @include('items.partials.insights')

    <div class="data-panel">
        <div class="data-panel-head data-panel-head-row">
            <h2 class="data-panel-title">Daftar Barang</h2>
            <div class="d-flex flex-wrap gap-2">
                @canany(['item export', 'item import'])
                    <div class="btn-group">
                        @can('item export')
                            <a href="{{ route('items.export') }}" class="btn btn-outline-secondary" title="Export Excel">
                                <i class="bi bi-download"></i> Export
                            </a>
                            <a href="{{ route('items.import.template') }}" class="btn btn-outline-secondary" title="Download format import">
                                <i class="bi bi-file-earmark-spreadsheet"></i> Format Import
                            </a>
                        @endcan
                        @can('item import')
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#import-modal">
                                <i class="bi bi-upload"></i> Import
                            </button>
                        @endcan
                    </div>
                @endcanany
                @can('item create')
                    <button type="button" class="btn btn-primary btn-add" data-action="create">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </button>
                @endcan
            </div>
        </div>
        <div class="data-panel-body">
            <div class="items-filter-bar border rounded p-3 mb-3 bg-light-subtle">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1" for="filter-category">Kategori</label>
                        <select id="filter-category" class="form-select form-control-clean">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1" for="filter-unit">Satuan</label>
                        <select id="filter-unit" class="form-select form-control-clean">
                            <option value="">Semua Satuan</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) ($filters['unit_id'] ?? '') === (string) $unit->id)>
                                    {{ $unit->name }}{{ $unit->abbreviation ? ' ('.$unit->abbreviation.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1" for="filter-low-stock">Stok Opname</label>
                        <select id="filter-low-stock" class="form-select form-control-clean">
                            <option value="">Semua Stok</option>
                            <option value="1" @selected($filters['low_stock'] ?? false)>Stok Menipis (stok ≤ batas minimum)</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" id="btn-apply-item-filter">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>
                        <button type="button" class="btn btn-light" id="btn-reset-item-filter">Reset</button>
                    </div>
                </div>
                <div class="form-hint-sm mt-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Stok menipis</strong> = stok saat ini sudah mencapai atau di bawah nilai <em>Stock Opname</em> (batas minimum) barang tersebut.
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="data-table" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th class="text-center">Stock Opname</th>
                            <th>Harga Jual</th>
                            <th>Harga Member</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @can('item import')
        <div class="modal fade" data-bs-backdrop="static" id="import-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-content-clean">
                    <form id="import-form" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Import Data Barang</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light border py-2 px-3 small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Gunakan file <strong>Format Import</strong> dari sistem. Kategori &amp; satuan sudah berupa dropdown di Excel.
                                Kode barang dibuat otomatis; stok awal tetap 0 (atur lewat Stok Masuk).
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File Excel (.xlsx) <span class="text-danger">*</span></label>
                                <input type="file" name="file" id="import-file" class="form-control form-control-clean" accept=".xlsx,.xls" required>
                                <div class="form-hint-sm mt-1">Maks. 5 MB. Hapus baris contoh sebelum upload.</div>
                            </div>
                            <div id="import-result" class="d-none"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-import-submit">
                                <i class="bi bi-upload"></i> Upload &amp; Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <div class="modal fade" data-bs-backdrop="static" id="form-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-content-clean">
                <form id="crud-form" method="post" action="" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" data-modal-title>Tambah Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="form-hint-sm mb-3"><i class="bi bi-info-circle me-1"></i> Kode barang dibuat otomatis (contoh: <code>BRG-20260701-0001</code>).</p>
                        <div class="row g-3">
                            @include('layouts.partials.entity-photo-field', ['label' => 'Barang', 'placeholderIcon' => 'bi-box-seam'])
                            <div class="col-md-6">
                                <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-clean" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select form-control-clean" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                <select name="unit_id" class="form-select form-control-clean" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}{{ $unit->abbreviation ? ' ('.$unit->abbreviation.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stock Opname</label>
                                <input type="number" name="stock_opname" class="form-control form-control-clean" min="0" value="0">
                                <div class="form-hint-sm">Batas minimum stok — jika stok ≤ nilai ini, muncul peringatan di dashboard.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Beli</label>
                                <input type="number" name="purchase_price" class="form-control form-control-clean" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Jual</label>
                                <input type="number" name="selling_price" class="form-control form-control-clean" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Member</label>
                                <input type="number" name="member_price" class="form-control form-control-clean" min="0" step="0.01" value="0">
                                <div class="form-hint-sm">Harga khusus pelanggan member (dipakai saat transaksi member).</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control form-control-clean" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-hint-sm">Stok hanya dapat diubah melalui menu Stok Masuk / Stok Keluar.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" data-bs-backdrop="static" id="show-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-content-clean">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.css">
@endpush

@push('js')
    <script>
        const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');

        const itemsIndexUrl = @json(route('items.index'));
        const itemsTable = $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: itemsIndexUrl,
                data: function (params) {
                    params.category_id = $('#filter-category').val();
                    params.unit_id = $('#filter-unit').val();
                    params.low_stock = $('#filter-low-stock').val();
                },
            },
            order: [[11, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'photo_thumb', orderable: false, searchable: false },
                { data: 'code', name: 'code' },
                { data: 'name', name: 'name' },
                { data: 'category_name', name: 'category.name', orderable: false },
                { data: 'unit_name', name: 'unit.name', orderable: false },
                { data: 'stock_display', name: 'stock', orderable: true, searchable: false },
                { data: 'stock_opname_display', name: 'stock_opname', orderable: true, searchable: false, className: 'text-center' },
                { data: 'selling_price', name: 'selling_price', orderable: false, searchable: false },
                { data: 'member_price', name: 'member_price', orderable: false, searchable: false },
                { data: 'status', name: 'is_active', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at', visible: false, searchable: false },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
        });

        function syncItemFilterUrl() {
            const params = new URLSearchParams();
            const categoryId = $('#filter-category').val();
            const unitId = $('#filter-unit').val();
            const lowStock = $('#filter-low-stock').val();

            if (categoryId) {
                params.set('category_id', categoryId);
            }
            if (unitId) {
                params.set('unit_id', unitId);
            }
            if (lowStock === '1') {
                params.set('low_stock', '1');
            }

            const qs = params.toString();
            const next = qs ? itemsIndexUrl + '?' + qs : itemsIndexUrl;
            window.history.replaceState(null, '', next);
        }

        function reloadItemsTable() {
            itemsTable.ajax.reload();
            syncItemFilterUrl();
        }

        $('#btn-apply-item-filter').on('click', reloadItemsTable);
        $('#filter-category, #filter-unit, #filter-low-stock').on('change', reloadItemsTable);

        $('#btn-reset-item-filter').on('click', function () {
            $('#filter-category, #filter-unit, #filter-low-stock').val('');
            reloadItemsTable();
        });

        AthaModalCrud.init({
            storeUrl: '{{ route('items.store') }}',
            updateUrlTemplate: '{{ route('items.update', '__ID__') }}',
            showUrlTemplate: '{{ route('items.show', '__ID__') }}',
            destroyUrlTemplate: '{{ route('items.destroy', '__ID__') }}',
            entityName: 'Barang',
            onFormReset: function (mode) {
                if (mode === 'create') {
                    $('#is_active').prop('checked', true);
                }
            },
            renderShow: function (d) {
                const unit = d.unit ? (d.unit.abbreviation || d.unit.name) : '-';
                const photo = d.photo_url
                    ? `<div class="text-center mb-3"><img src="${d.photo_url}" alt="${d.name}" class="entity-photo-detail"></div>`
                    : '';
                return photo + `
                    <dl class="detail-list mb-0">
                        <dt>Kode</dt><dd>${d.code}</dd>
                        <dt>Nama</dt><dd>${d.name}</dd>
                        <dt>Kategori</dt><dd>${d.category?.name || '-'}</dd>
                        <dt>Satuan</dt><dd>${unit}</dd>
                        <dt>Stok</dt><dd>${Number(d.stock).toLocaleString('id-ID')}</dd>
                        <dt>Stock Opname</dt><dd>${Number(d.stock_opname).toLocaleString('id-ID')}</dd>
                        <dt>Harga Beli</dt><dd>${rupiah(d.purchase_price)}</dd>
                        <dt>Harga Jual</dt><dd>${rupiah(d.selling_price)}</dd>
                        <dt>Harga Member</dt><dd>${rupiah(d.member_price)}</dd>
                        <dt>Status</dt><dd>${d.is_active ? 'Aktif' : 'Nonaktif'}</dd>
                        <dt>Deskripsi</dt><dd>${d.description || '-'}</dd>
                    </dl>`;
            },
        });

        @can('item import')
        $('#import-form').on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#btn-import-submit').prop('disabled', true);
            const $result = $('#import-result').addClass('d-none').empty();
            const formData = new FormData(this);

            $.ajax({
                url: '{{ route('items.import') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { Accept: 'application/json' },
                success: function (res) {
                    let html = '<div class="alert alert-success py-2 small mb-0">' + res.message + '</div>';
                    if (res.data?.errors?.length) {
                        html += '<ul class="small text-muted mt-2 mb-0 ps-3">';
                        res.data.errors.slice(0, 10).forEach(function (err) {
                            html += '<li>' + err + '</li>';
                        });
                        if (res.data.errors.length > 10) {
                            html += '<li>...dan ' + (res.data.errors.length - 10) + ' lainnya</li>';
                        }
                        html += '</ul>';
                    }
                    $result.html(html).removeClass('d-none');

                    if (res.data?.created > 0) {
                        itemsTable.ajax.reload(null, false);
                    }

                    Swal.fire({
                        icon: res.data?.created > 0 ? 'success' : 'warning',
                        title: res.message,
                        timer: res.data?.errors?.length ? 0 : 2200,
                        showConfirmButton: !!res.data?.errors?.length,
                    });

                    if (res.data?.created > 0 && !res.data?.errors?.length) {
                        bootstrap.Modal.getInstance(document.getElementById('import-modal'))?.hide();
                        $('#import-form')[0].reset();
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Gagal mengimport file.';
                    Swal.fire({ icon: 'error', title: msg });
                    if (xhr.responseJSON?.errors?.file) {
                        $result.html('<div class="alert alert-danger py-2 small mb-0">' + xhr.responseJSON.errors.file[0] + '</div>').removeClass('d-none');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false);
                },
            });
        });

        $('#import-modal').on('hidden.bs.modal', function () {
            $('#import-form')[0].reset();
            $('#import-result').addClass('d-none').empty();
        });
        @endcan
    </script>
@endpush
