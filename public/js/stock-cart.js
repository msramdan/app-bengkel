/**
 * Keranjang stok masuk / keluar — multi barang
 */
(function ($) {
    'use strict';

    window.AthaStockCart = {
        init: function (options) {
            const settings = $.extend({
                table: '#data-table',
                formModal: '#form-modal',
                showModal: '#show-modal',
                storeUrl: '',
                batchShowUrl: '',
                items: [],
                type: 'in',
                quantityPrefix: '+',
            }, options);

            const cart = [];
            const $table = $(settings.table);
            const $formModal = $(settings.formModal);
            const $showModal = $(settings.showModal);
            const $cartBody = $('#stock-cart-body');
            const $cartEmpty = $('#stock-cart-empty');
            const $cartCount = $('#stock-cart-count');
            const formModal = bootstrap.Modal.getOrCreateInstance($formModal[0], { backdrop: 'static' });
            const showModal = bootstrap.Modal.getOrCreateInstance($showModal[0], { backdrop: 'static' });

            function findItem(id) {
                return settings.items.find(function (item) {
                    return String(item.id) === String(id);
                });
            }

            function renderCart() {
                $cartBody.empty();
                $cartCount.text(cart.length);

                if (!cart.length) {
                    $cartEmpty.show();
                    $('#btn-submit-cart').prop('disabled', true);
                    return;
                }

                $cartEmpty.hide();
                $('#btn-submit-cart').prop('disabled', false);

                cart.forEach(function (line, index) {
                    const prefix = settings.type === 'in' ? '+' : '-';
                    $cartBody.append(`
                        <tr>
                            <td>
                                <div class="fw-medium">${line.code}</div>
                                <div class="text-muted small">${line.name}</div>
                            </td>
                            <td class="text-center">${Number(line.stock).toLocaleString('id-ID')}</td>
                            <td class="text-center fw-semibold">${prefix}${Number(line.quantity).toLocaleString('id-ID')}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-cart" data-index="${index}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }

            function resetForm() {
                cart.length = 0;
                renderCart();
                $('#stock-add-form')[0].reset();
                if (window.AthaSearchableSelect) {
                    AthaSearchableSelect.clear('#cart_item_id');
                } else {
                    $('#cart_item_id').val(null).trigger('change');
                }
                $('#stock-hint').text('');
                $('#reference_no, #notes').val('');
            }

            $('[data-action="create"]').on('click', function () {
                resetForm();
                formModal.show();
            });

            $('#stock-add-form').on('submit', function (e) {
                e.preventDefault();
                const itemId = $('#cart_item_id').val();
                const quantity = parseInt($('#cart_quantity').val(), 10);
                const item = findItem(itemId);

                if (!item) {
                    Swal.fire({ icon: 'warning', title: 'Pilih barang terlebih dahulu.' });
                    return;
                }

                if (!quantity || quantity < 1) {
                    Swal.fire({ icon: 'warning', title: 'Jumlah harus lebih dari 0.' });
                    return;
                }

                if (settings.type === 'out' && quantity > item.stock) {
                    Swal.fire({ icon: 'error', title: 'Stok tidak mencukupi.', text: 'Tersedia: ' + Number(item.stock).toLocaleString('id-ID') });
                    return;
                }

                const existing = cart.find(function (line) {
                    return String(line.item_id) === String(itemId);
                });

                if (existing) {
                    const newQty = existing.quantity + quantity;
                    if (settings.type === 'out' && newQty > item.stock) {
                        Swal.fire({ icon: 'error', title: 'Total melebihi stok tersedia.' });
                        return;
                    }
                    existing.quantity = newQty;
                } else {
                    cart.push({
                        item_id: item.id,
                        code: item.code,
                        name: item.name,
                        stock: item.stock,
                        quantity: quantity,
                    });
                }

                renderCart();
                $('#cart_quantity').val('');
                if (window.AthaSearchableSelect) {
                    AthaSearchableSelect.clear('#cart_item_id');
                } else {
                    $('#cart_item_id').val(null).trigger('change');
                }
            });

            $cartBody.on('click', '.btn-remove-cart', function () {
                cart.splice($(this).data('index'), 1);
                renderCart();
            });

            $('#cart_item_id').on('change', function () {
                const item = findItem($(this).val());
                if (!item) {
                    $('#stock-hint').text('');
                    $('#cart_quantity').removeAttr('max');
                    return;
                }
                $('#stock-hint').text('Stok tersedia: ' + Number(item.stock).toLocaleString('id-ID'));
                if (settings.type === 'out') {
                    $('#cart_quantity').attr('max', item.stock);
                }
            });

            $('#btn-submit-cart').on('click', function () {
                if (!cart.length) {
                    return;
                }

                const $btn = $(this).prop('disabled', true);

                $.ajax({
                    url: settings.storeUrl,
                    type: 'POST',
                    headers: { Accept: 'application/json' },
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        reference_no: $('#reference_no').val(),
                        notes: $('#notes').val(),
                        items: cart.map(function (line) {
                            return { item_id: line.item_id, quantity: line.quantity };
                        }),
                    },
                    success: function (res) {
                        formModal.hide();
                        Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false });
                        $table.DataTable().ajax.reload(null, false);
                        location.reload();
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Gagal menyimpan.' });
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    },
                });
            });

            $table.on('click', '[data-action="show-batch"]', function () {
                const batchNo = $(this).data('batch');
                const $body = $showModal.find('.modal-body');
                const prefix = settings.type === 'in' ? '+' : '-';

                $body.html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>');
                showModal.show();

                $.get(settings.batchShowUrl.replace('__BATCH__', encodeURIComponent(batchNo)), function (res) {
                    const d = res.data;
                    let rows = d.items.map(function (item) {
                        return `<tr>
                            <td>${item.code} — ${item.name}</td>
                            <td class="text-center">${prefix}${Number(item.quantity).toLocaleString('id-ID')}</td>
                            <td class="text-center text-muted small">${Number(item.stock_before).toLocaleString('id-ID')} → ${Number(item.stock_after).toLocaleString('id-ID')}</td>
                        </tr>`;
                    }).join('');

                    $body.html(`
                        <dl class="detail-list mb-3">
                            <dt>No. Transaksi</dt><dd>${d.batch_no}</dd>
                            <dt>Referensi Eksternal</dt><dd>${d.reference_no || '-'}</dd>
                            <dt>Catatan</dt><dd>${d.notes || '-'}</dd>
                            <dt>Petugas</dt><dd>${d.user?.name || '-'}</dd>
                            <dt>Waktu</dt><dd>${new Date(d.created_at).toLocaleString('id-ID')}</dd>
                        </dl>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-center">Stok</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    `);
                }).fail(function () {
                    $body.html('<p class="text-danger mb-0">Gagal memuat detail.</p>');
                });
            });
        },
    };
})(jQuery);
