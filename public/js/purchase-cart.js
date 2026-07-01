(function ($) {
    'use strict';

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    window.AthaPurchaseCart = {
        init: function (options) {
            const settings = $.extend({
                storeUrl: '',
                redirectUrl: '',
                items: [],
            }, options);

            const cart = [];
            const $itemsBody = $('#items-cart-body');
            const $itemsEmpty = $('#items-cart-empty');
            const $submit = $('#btn-submit');

            function findItem(id) {
                return settings.items.find(function (i) { return String(i.id) === String(id); });
            }

            function recalcLine(line) {
                line.subtotal = Math.round(line.unit_price * line.quantity);
            }

            function renderCart() {
                $itemsBody.empty();
                if (!cart.length) {
                    $itemsEmpty.show();
                } else {
                    $itemsEmpty.hide();
                    cart.forEach(function (line, index) {
                        $itemsBody.append(`
                            <tr>
                                <td><div class="fw-medium">${line.code}</div><div class="text-muted small">${line.name}</div></td>
                                <td class="text-center" style="width:90px">
                                    <input type="number" class="form-control form-control-clean cart-qty-input"
                                        data-index="${index}"
                                        value="${line.quantity}" min="1" step="1">
                                </td>
                                <td class="text-end">${formatRp(line.unit_price)}</td>
                                <td class="text-end fw-medium cart-subtotal">${formatRp(line.subtotal)}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-index="${index}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }
                updateSummary();
            }

            function updateSummary() {
                const subtotal = cart.reduce(function (s, l) { return s + l.subtotal; }, 0);
                let discount = parseFloat($('#discount').val()) || 0;
                discount = Math.max(0, Math.min(discount, subtotal));
                const total = subtotal - discount;
                const paymentOk = $('#payment_method').val() !== 'transfer' || !!$('#bank_account_id').val();

                $('#sum-subtotal').text(formatRp(subtotal));
                $('#sum-discount').text('- ' + formatRp(discount));
                $('#sum-total').text(formatRp(total));
                $submit.prop('disabled', cart.length === 0 || !paymentOk);
            }

            $('#discount, #payment_method, #bank_account_id').on('change input', updateSummary);

            $('#item_select').on('change', function () {
                const item = findItem($(this).val());
                $('#item-hint').text(item
                    ? 'Stok: ' + Number(item.stock).toLocaleString('id-ID') + ' | Harga beli: ' + formatRp(item.purchase_price)
                    : '');
            });

            $('#btn-add-item').on('click', function () {
                const itemId = $('#item_select').val();
                const qty = parseInt($('#item_qty').val(), 10);
                const item = findItem(itemId);

                if (!item) {
                    Swal.fire({ icon: 'warning', title: 'Pilih barang terlebih dahulu.' });
                    return;
                }
                if (!qty || qty < 1) {
                    Swal.fire({ icon: 'warning', title: 'Qty harus lebih dari 0.' });
                    return;
                }

                const existing = cart.find(function (l) { return String(l.item_id) === String(itemId); });
                const unitPrice = parseFloat(item.purchase_price);
                const newQty = (existing ? existing.quantity : 0) + qty;

                if (existing) {
                    existing.quantity = newQty;
                    recalcLine(existing);
                } else {
                    cart.push({
                        item_id: item.id,
                        code: item.code,
                        name: item.name,
                        quantity: qty,
                        unit_price: unitPrice,
                        subtotal: unitPrice * qty,
                    });
                }

                renderCart();
                $('#item_qty').val('');
                if (window.AthaSearchableSelect) {
                    AthaSearchableSelect.clear('#item_select');
                } else {
                    $('#item_select').val(null).trigger('change');
                }
            });

            $itemsBody.on('click', '.btn-remove-item', function () {
                cart.splice($(this).data('index'), 1);
                renderCart();
            });

            function handleQtyInput($input, showError) {
                const index = parseInt($input.data('index'), 10);
                const line = cart[index];
                if (!line) {
                    return false;
                }

                const qty = parseInt($input.val(), 10);
                if (isNaN(qty) || qty < 1) {
                    if (showError) {
                        $input.val(line.quantity);
                        Swal.fire({ icon: 'warning', title: 'Qty harus minimal 1.' });
                    }
                    return false;
                }

                line.quantity = qty;
                recalcLine(line);
                $input.val(qty);
                $input.closest('tr').find('.cart-subtotal').text(formatRp(line.subtotal));
                updateSummary();
                return true;
            }

            function validateCartQty() {
                for (let i = 0; i < cart.length; i++) {
                    if (cart[i].quantity < 1) {
                        Swal.fire({ icon: 'warning', title: 'Qty barang tidak valid.', text: cart[i].name });
                        return false;
                    }
                }
                return true;
            }

            $itemsBody.on('input', '.cart-qty-input', function () {
                handleQtyInput($(this), false);
            });

            $itemsBody.on('blur', '.cart-qty-input', function () {
                handleQtyInput($(this), true);
            });

            $('#discount').on('change input', updateSummary);

            $('#purchase-form').on('submit', function (e) {
                e.preventDefault();
                if (!cart.length) {
                    return;
                }

                if (!validateCartQty()) {
                    return;
                }

                if ($('#payment_method').val() === 'transfer' && !$('#bank_account_id').val()) {
                    Swal.fire({ icon: 'warning', title: 'Pilih akun bank untuk transfer.' });
                    return;
                }

                $submit.prop('disabled', true);

                $.ajax({
                    url: settings.storeUrl,
                    type: 'POST',
                    headers: { Accept: 'application/json' },
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        supplier_name: $('#supplier_name').val(),
                        discount: $('#discount').val() || 0,
                        notes: $('#notes').val(),
                        payment_method: $('#payment_method').val(),
                        bank_account_id: $('#bank_account_id').val() || null,
                        items: cart.map(function (l) {
                            return { item_id: l.item_id, quantity: l.quantity };
                        }),
                    },
                    success: function (res) {
                        Swal.fire({
                            icon: 'success',
                            title: res.message,
                            text: res.data?.purchase_no || '',
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(function () {
                            window.location.href = settings.redirectUrl;
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Gagal menyimpan.' });
                        $submit.prop('disabled', false);
                        updateSummary();
                    },
                });
            });

            updateSummary();
        },
    };
})(jQuery);
