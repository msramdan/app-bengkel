/**
 * Edit transaksi — koreksi barang/jasa, stok, dan pembayaran
 */
(function ($) {
    'use strict';

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    window.AthaTransactionEdit = {
        init: function (options) {
            const settings = $.extend({
                updateUrl: '',
                redirectUrl: '',
                itemAvailabilityUrl: '',
                defaultTechPercent: 20,
                stockCredit: {},
                usesMemberPricing: false,
                items: [],
                services: [],
            }, options);

            let itemCart = [];
            let serviceCart = [];
            const stockCredit = settings.stockCredit || {};

            const $itemsBody = $('#items-cart-body');
            const $itemsEmpty = $('#items-cart-empty');
            const $servicesBody = $('#services-cart-body');
            const $servicesEmpty = $('#services-cart-empty');
            const $submit = $('#btn-submit');

            function getDefaultItemPrice(item) {
                if (!item) {
                    return 0;
                }
                if (settings.usesMemberPricing) {
                    const memberPrice = parseFloat(item.member_price);
                    if (!isNaN(memberPrice) && memberPrice > 0) {
                        return memberPrice;
                    }
                }
                return parseFloat(item.selling_price) || 0;
            }

            function findItem(itemId) {
                return settings.items.find(function (i) { return String(i.id) === String(itemId); });
            }

            function findService(serviceId) {
                return settings.services.find(function (s) { return String(s.id) === String(serviceId); });
            }

            function getItemStock(itemId) {
                const item = findItem(itemId);
                return item ? parseInt(item.stock, 10) || 0 : 0;
            }

            function getAvailableStock(itemId) {
                return getItemStock(itemId) + (parseInt(stockCredit[itemId], 10) || 0);
            }

            function resolveTechPercent() {
                const $selected = $('#technician_id').find('option:selected');
                const fromTech = parseFloat($selected.data('commission-percent'));
                if ($selected.val() && !isNaN(fromTech)) {
                    return fromTech;
                }
                return settings.defaultTechPercent;
            }

            function recalcLine(line) {
                line.subtotal = Math.round(line.unit_price * line.quantity);
            }

            function renderItemCart() {
                $itemsBody.empty();
                if (!itemCart.length) {
                    $itemsEmpty.removeClass('d-none');
                } else {
                    $itemsEmpty.addClass('d-none');
                    itemCart.forEach(function (line, index) {
                        const available = getAvailableStock(line.item_id);
                        $itemsBody.append(`
                            <tr>
                                <td>
                                    <div class="fw-medium">${line.code}</div>
                                    <div class="text-muted small">${line.name}</div>
                                </td>
                                <td class="text-center" style="width:90px">
                                    <input type="number" class="form-control form-control-clean cart-qty-input"
                                        data-type="item" data-index="${index}"
                                        value="${line.quantity}" min="1" step="1"
                                        title="Stok tersedia: ${available.toLocaleString('id-ID')}">
                                </td>
                                <td class="text-end" style="width:130px">
                                    <input type="number" class="form-control form-control-clean cart-price-input text-end"
                                        data-type="item" data-index="${index}"
                                        value="${line.unit_price}" min="0" step="1">
                                </td>
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

            function renderServiceCart() {
                $servicesBody.empty();
                if (!serviceCart.length) {
                    $servicesEmpty.removeClass('d-none');
                    $('#tech-required').addClass('d-none');
                } else {
                    $servicesEmpty.addClass('d-none');
                    $('#tech-required').removeClass('d-none');
                    serviceCart.forEach(function (line, index) {
                        $servicesBody.append(`
                            <tr>
                                <td>
                                    <div class="fw-medium">${line.code}</div>
                                    <div class="text-muted small">${line.name}</div>
                                </td>
                                <td class="text-center" style="width:90px">
                                    <input type="number" class="form-control form-control-clean cart-qty-input"
                                        data-type="service" data-index="${index}"
                                        value="${line.quantity}" min="1" step="1">
                                </td>
                                <td class="text-end" style="width:130px">
                                    <input type="number" class="form-control form-control-clean cart-price-input text-end"
                                        data-type="service" data-index="${index}"
                                        value="${line.unit_price}" min="0" step="1">
                                </td>
                                <td class="text-end fw-medium cart-subtotal">${formatRp(line.subtotal)}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-service" data-index="${index}">
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
                const subItems = itemCart.reduce(function (s, l) { return s + l.subtotal; }, 0);
                const subServices = serviceCart.reduce(function (s, l) { return s + l.subtotal; }, 0);
                const gross = subItems + subServices;
                let discount = parseFloat($('#discount').val()) || 0;
                discount = Math.max(0, Math.min(discount, gross));

                const techPercent = resolveTechPercent();
                const ownerPercent = 100 - techPercent;
                const techCommission = Math.round(subServices * techPercent / 100);
                const ownerService = Math.round(subServices * ownerPercent / 100);
                const ownerItems = subItems;
                const ownerTotal = ownerService + ownerItems;
                const total = gross - discount;

                $('#sum-items').text(formatRp(subItems));
                $('#sum-services').text(formatRp(subServices));
                $('#sum-discount').text('- ' + formatRp(discount));
                $('#sum-total').text(formatRp(total));
                $('#label-tech-percent').text(techPercent);
                $('#label-owner-percent').text(ownerPercent);
                $('#sum-tech-commission').text(formatRp(techCommission));
                $('#sum-owner-service').text(formatRp(ownerService));
                $('#sum-owner-items').text(formatRp(ownerItems));
                $('#sum-owner-total').text(formatRp(ownerTotal));

                if (window.AthaPaymentFields) {
                    window.AthaPaymentFields.updateCashChange(total);
                }

                const hasLines = itemCart.length || serviceCart.length;
                const needsTech = serviceCart.length > 0;
                const hasTech = !!$('#technician_id').val();
                const paymentOk = $('#payment_method').val() !== 'transfer' || !!$('#bank_account_id').val();
                const cashOk = $('#payment_method').val() !== 'cash'
                    || (window.AthaPaymentFields && window.AthaPaymentFields.getCashPayment(total).sufficient);

                $submit.prop('disabled', !(hasLines && (!needsTech || hasTech) && paymentOk && cashOk));
            }

            function validateBeforeSave() {
                if (!itemCart.length && !serviceCart.length) {
                    Swal.fire({ icon: 'warning', title: 'Tambahkan minimal satu barang atau jasa.' });
                    return false;
                }

                if (serviceCart.length && !$('#technician_id').val()) {
                    Swal.fire({ icon: 'warning', title: 'Pilih teknisi untuk transaksi servis.' });
                    return false;
                }

                for (let i = 0; i < itemCart.length; i++) {
                    const line = itemCart[i];
                    const available = getAvailableStock(line.item_id);
                    if (line.quantity < 1) {
                        Swal.fire({ icon: 'warning', title: 'Qty barang tidak valid.', text: line.name });
                        return false;
                    }
                    if (line.quantity > available) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Stok tidak mencukupi.',
                            text: line.name + ' — tersedia: ' + available.toLocaleString('id-ID'),
                        });
                        return false;
                    }
                }

                if ($('#payment_method').val() === 'transfer' && !$('#bank_account_id').val()) {
                    Swal.fire({ icon: 'warning', title: 'Pilih akun bank untuk transfer.' });
                    return false;
                }

                if ($('#payment_method').val() === 'cash') {
                    const subItems = itemCart.reduce(function (s, l) { return s + l.subtotal; }, 0);
                    const subServices = serviceCart.reduce(function (s, l) { return s + l.subtotal; }, 0);
                    const gross = subItems + subServices;
                    let discount = parseFloat($('#discount').val()) || 0;
                    discount = Math.max(0, Math.min(discount, gross));
                    const total = gross - discount;
                    const cash = window.AthaPaymentFields.getCashPayment(total);

                    if (!cash.paid || cash.paid < total) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Uang diterima kurang',
                            text: 'Total ' + formatRp(total) + '. Masukkan uang minimal sebesar total.',
                        });
                        return false;
                    }
                }

                return true;
            }

            function buildPayload() {
                const payload = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT',
                    technician_id: $('#technician_id').val() || null,
                    discount: $('#discount').val() || 0,
                    notes: $('#notes').val(),
                    payment_method: $('#payment_method').val(),
                    bank_account_id: $('#bank_account_id').val() || null,
                    items: itemCart.map(function (l) {
                        return { item_id: l.item_id, quantity: l.quantity, unit_price: l.unit_price };
                    }),
                    services: serviceCart.map(function (l) {
                        return { workshop_service_id: l.workshop_service_id, quantity: l.quantity, unit_price: l.unit_price };
                    }),
                };

                if ($('#payment_method').val() === 'cash') {
                    payload.amount_paid = $('#amount_paid').val();
                }

                return payload;
            }

            function refreshItemStock() {
                if (!settings.itemAvailabilityUrl || !itemCart.length) {
                    return $.Deferred().resolve().promise();
                }

                const ids = itemCart.map(function (l) { return l.item_id; }).join(',');

                return $.getJSON(settings.itemAvailabilityUrl, { ids: ids }).done(function (res) {
                    (res.data || []).forEach(function (row) {
                        const item = findItem(row.id);
                        if (item) {
                            item.stock = row.stock;
                        }
                    });
                });
            }

            function loadInitialState() {
                const initial = settings.initial || {};

                itemCart = (initial.items || []).map(function (line) {
                    const copy = { ...line };
                    recalcLine(copy);
                    return copy;
                });

                serviceCart = (initial.services || []).map(function (line) {
                    const copy = { ...line };
                    recalcLine(copy);
                    return copy;
                });

                if (initial.technician_id) {
                    $('#technician_id').val(initial.technician_id).trigger('change');
                }

                $('#discount').val(initial.discount || 0);
                $('#notes').val(initial.notes || '');

                if (initial.payment_method) {
                    $('#payment_method').val(initial.payment_method).trigger('change');
                }

                if (initial.bank_account_id) {
                    $('#bank_account_id').val(initial.bank_account_id).trigger('change');
                }

                if (initial.amount_paid) {
                    $('#amount_paid').val(initial.amount_paid);
                }

                renderItemCart();
                renderServiceCart();
            }

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

                const existing = itemCart.find(function (l) { return String(l.item_id) === String(itemId); });
                const newQty = (existing ? existing.quantity : 0) + qty;
                const available = getAvailableStock(itemId);

                if (newQty > available) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Stok tidak mencukupi.',
                        text: item.name + ' — tersedia: ' + available.toLocaleString('id-ID'),
                    });
                    return;
                }

                const unitPrice = getDefaultItemPrice(item);
                if (existing) {
                    existing.quantity = newQty;
                    recalcLine(existing);
                } else {
                    itemCart.push({
                        item_id: item.id,
                        code: item.code,
                        name: item.name,
                        quantity: qty,
                        unit_price: unitPrice,
                        subtotal: unitPrice * qty,
                    });
                }

                renderItemCart();
                $('#item_qty').val('');
                if (window.AthaSearchableSelect) {
                    AthaSearchableSelect.clear('#item_select');
                } else {
                    $('#item_select').val('').trigger('change');
                }
            });

            $('#btn-add-service').on('click', function () {
                const serviceId = $('#service_select').val();
                const qty = parseInt($('#service_qty').val(), 10) || 1;
                const service = findService(serviceId);

                if (!service) {
                    Swal.fire({ icon: 'warning', title: 'Pilih jasa terlebih dahulu.' });
                    return;
                }

                const existing = serviceCart.find(function (l) { return String(l.workshop_service_id) === String(serviceId); });
                const unitPrice = parseFloat(service.price);
                const newQty = (existing ? existing.quantity : 0) + qty;

                if (existing) {
                    existing.quantity = newQty;
                    recalcLine(existing);
                } else {
                    serviceCart.push({
                        workshop_service_id: service.id,
                        code: service.code,
                        name: service.name,
                        quantity: qty,
                        unit_price: unitPrice,
                        subtotal: unitPrice * qty,
                    });
                }

                renderServiceCart();
                $('#service_qty').val('1');
                if (window.AthaSearchableSelect) {
                    AthaSearchableSelect.clear('#service_select');
                } else {
                    $('#service_select').val('').trigger('change');
                }
            });

            $itemsBody.on('click', '.btn-remove-item', function () {
                itemCart.splice($(this).data('index'), 1);
                renderItemCart();
            });

            $servicesBody.on('click', '.btn-remove-service', function () {
                serviceCart.splice($(this).data('index'), 1);
                renderServiceCart();
            });

            function handlePriceInput($input) {
                const index = parseInt($input.data('index'), 10);
                const type = $input.data('type');
                const cart = type === 'item' ? itemCart : serviceCart;
                const line = cart[index];
                if (!line) {
                    return;
                }

                let price = parseFloat($input.val());
                if (isNaN(price) || price < 0) {
                    price = 0;
                }

                line.unit_price = price;
                recalcLine(line);
                $input.closest('tr').find('.cart-subtotal').text(formatRp(line.subtotal));
                updateSummary();
            }

            function handleQtyInput($input, showError) {
                const index = parseInt($input.data('index'), 10);
                const type = $input.data('type');
                const cart = type === 'item' ? itemCart : serviceCart;
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

                if (cart === itemCart) {
                    const available = getAvailableStock(line.item_id);
                    if (qty > available) {
                        if (showError) {
                            $input.val(line.quantity);
                            Swal.fire({
                                icon: 'error',
                                title: 'Stok tidak mencukupi.',
                                text: line.name + ' — tersedia: ' + available.toLocaleString('id-ID'),
                            });
                        }
                        return false;
                    }
                }

                line.quantity = qty;
                recalcLine(line);
                $input.val(qty);
                $input.closest('tr').find('.cart-subtotal').text(formatRp(line.subtotal));
                updateSummary();
                return true;
            }

            $itemsBody.on('input', '.cart-qty-input', function () { handleQtyInput($(this), false); });
            $itemsBody.on('blur', '.cart-qty-input', function () { handleQtyInput($(this), true); });
            $servicesBody.on('input', '.cart-qty-input', function () { handleQtyInput($(this), false); });
            $servicesBody.on('blur', '.cart-qty-input', function () { handleQtyInput($(this), true); });
            $itemsBody.on('input change', '.cart-price-input', function () { handlePriceInput($(this)); });
            $servicesBody.on('input change', '.cart-price-input', function () { handlePriceInput($(this)); });

            $('#technician_id, #discount, #payment_method, #bank_account_id, #amount_paid').on('change input', updateSummary);
            $(document).on('atha:payment-fields-changed', updateSummary);

            $('#transaction-form').on('submit', function (e) {
                e.preventDefault();

                if (!validateBeforeSave()) {
                    return;
                }

                $submit.prop('disabled', true);

                $.ajax({
                    url: settings.updateUrl,
                    type: 'POST',
                    headers: { Accept: 'application/json' },
                    data: buildPayload(),
                    success: function (res) {
                        Swal.fire({
                            icon: 'success',
                            title: res.message,
                            timer: 1600,
                            showConfirmButton: false,
                        }).then(function () {
                            window.location.href = settings.redirectUrl;
                        });
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON?.message || 'Gagal memperbarui transaksi.';
                        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                        $submit.prop('disabled', false);
                        refreshItemStock().always(updateSummary);
                    },
                });
            });

            loadInitialState();
            refreshItemStock().always(updateSummary);
        },
    };
})(jQuery);
