/**
 * Keranjang transaksi — barang + jasa + ringkasan komisi
 */
(function ($) {
    'use strict';

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    window.AthaTransactionCart = {
        init: function (options) {
            const settings = $.extend({
                storeUrl: '',
                redirectUrl: '',
                techPercent: 20,
                ownerPercent: 80,
                defaultTechPercent: 20,
                items: [],
                services: [],
            }, options);

            function resolveTechPercent() {
                const $selected = $('#technician_id').find('option:selected');
                const fromTech = parseFloat($selected.data('commission-percent'));
                if ($selected.val() && !isNaN(fromTech)) {
                    return fromTech;
                }
                return settings.defaultTechPercent;
            }

            function getCustomerMode() {
                const val = $('#customer_id').val();
                if (val === '__umum__') {
                    return 'umum';
                }
                if (val === '__new__') {
                    return 'new';
                }
                if (val) {
                    return 'existing';
                }
                return null;
            }

            function hasCustomerSelected() {
                const mode = getCustomerMode();
                if (mode === 'umum' || mode === 'existing') {
                    return true;
                }
                if (mode === 'new') {
                    return !!$('#new_customer_name').val().trim();
                }
                return false;
            }

            function usesMemberPricing() {
                if (getCustomerMode() !== 'existing') {
                    return false;
                }

                const raw = $('#customer_id').find('option:selected').data('isMember');

                return raw === 1 || raw === '1' || raw === true;
            }

            function getDefaultItemPrice(item) {
                if (!item) {
                    return 0;
                }

                if (usesMemberPricing()) {
                    const memberPrice = parseFloat(item.member_price);
                    if (!isNaN(memberPrice) && memberPrice > 0) {
                        return memberPrice;
                    }
                }

                return parseFloat(item.selling_price) || 0;
            }

            function updateCustomerRemark() {
                const mode = getCustomerMode();
                const $remark = $('#customer-type-remark');
                const $badge = $('#customer-type-badge');

                if (!hasCustomerSelected()) {
                    $remark.addClass('d-none');
                    return;
                }

                let label = 'Pelanggan Biasa';
                let badgeClass = 'badge bg-secondary-subtle text-secondary';

                if (mode === 'existing' && usesMemberPricing()) {
                    label = 'Member';
                    badgeClass = 'badge bg-primary-subtle text-primary';
                } else if (mode === 'umum') {
                    label = 'Pelanggan Biasa (Umum)';
                } else if (mode === 'new') {
                    label = 'Pelanggan Biasa (Baru)';
                }

                $badge.attr('class', badgeClass).text(label);
                $remark.removeClass('d-none');
            }

            function toggleItemsSectionLock() {
                const locked = !hasCustomerSelected();

                $('#items-section-lock').toggleClass('d-none', !locked);
                $('#item_select, #item_qty, #btn-add-item').prop('disabled', locked);
            }

            function updateItemHint() {
                const item = findItem($('#item_select').val());
                if (!item) {
                    $('#item-hint').text('');
                    return;
                }

                const price = getDefaultItemPrice(item);
                const priceLabel = usesMemberPricing() ? 'Harga Member' : 'Harga Jual';
                $('#item-hint').text(
                    'Stok: ' + Number(item.stock).toLocaleString('id-ID') + ' | ' + priceLabel + ': ' + formatRp(price)
                );
            }

            function recalculateItemCartPrices() {
                itemCart.forEach(function (line) {
                    const item = findItem(line.item_id);
                    if (!item) {
                        return;
                    }

                    line.unit_price = getDefaultItemPrice(item);
                    recalcLine(line);
                });
            }

            function toggleNewCustomerFields() {
                if (getCustomerMode() === 'new') {
                    $('#new-customer-fields').removeClass('d-none');
                } else {
                    $('#new-customer-fields').addClass('d-none');
                }
            }

            const itemCart = [];
            const serviceCart = [];
            const $itemsBody = $('#items-cart-body');
            const $servicesBody = $('#services-cart-body');
            const $itemsEmpty = $('#items-cart-empty');
            const $servicesEmpty = $('#services-cart-empty');
            const $submit = $('#btn-submit');

            function findItem(id) {
                return settings.items.find(function (i) { return String(i.id) === String(id); });
            }

            function findService(id) {
                return settings.services.find(function (s) { return String(s.id) === String(id); });
            }

            function getItemStock(itemId) {
                const item = findItem(itemId);
                return item ? parseInt(item.stock, 10) : 0;
            }

            function recalcLine(line) {
                line.subtotal = Math.round(line.unit_price * line.quantity);
            }

            function renderItemCart() {
                $itemsBody.empty();
                if (!itemCart.length) {
                    $itemsEmpty.show();
                } else {
                    $itemsEmpty.hide();
                    itemCart.forEach(function (line, index) {
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
                                        title="Stok tersedia: ${getItemStock(line.item_id).toLocaleString('id-ID')}">
                                </td>
                                <td class="text-end" style="width:130px">
                                    <input type="number" class="form-control form-control-clean cart-price-input text-end"
                                        data-type="item" data-index="${index}"
                                        value="${line.unit_price}" min="0" step="1" title="Harga transaksi (bisa diubah)">
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
                    $servicesEmpty.show();
                    $('#tech-required').addClass('d-none');
                } else {
                    $servicesEmpty.hide();
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
                                        value="${line.unit_price}" min="0" step="1" title="Harga transaksi (bisa diubah)">
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

                const hasLines = itemCart.length || serviceCart.length;
                const hasCustomer = hasCustomerSelected();
                const needsTech = serviceCart.length > 0;
                const hasTech = !!$('#technician_id').val();
                const paymentOk = $('#payment_method').val() !== 'transfer' || !!$('#bank_account_id').val();

                $submit.prop('disabled', !(hasLines && hasCustomer && (!needsTech || hasTech) && paymentOk));
            }

            $('#customer_id').on('change', function () {
                toggleNewCustomerFields();
                updateCustomerRemark();
                recalculateItemCartPrices();
                toggleItemsSectionLock();
                updateItemHint();
                renderItemCart();
                updateSummary();
            });

            $('#new_customer_name, #new_customer_phone, #new_customer_address').on('input', function () {
                updateCustomerRemark();
                toggleItemsSectionLock();
                updateSummary();
            });

            $('#customer_id, #technician_id, #discount, #payment_method, #bank_account_id').on('change input', updateSummary);

            $('#item_select').on('change', updateItemHint);

            $('#btn-add-item').on('click', function () {
                if (!hasCustomerSelected()) {
                    Swal.fire({ icon: 'warning', title: 'Pilih pelanggan terlebih dahulu sebelum menambah barang.' });
                    return;
                }

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

                if (newQty > item.stock) {
                    Swal.fire({ icon: 'error', title: 'Stok tidak mencukupi.', text: 'Tersedia: ' + Number(item.stock).toLocaleString('id-ID') });
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
                    $('#item_select').val(null).trigger('change');
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
                if (qty < 1) {
                    Swal.fire({ icon: 'warning', title: 'Qty harus lebih dari 0.' });
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

            function applyQtyToLine($input, cart, line, qty, showError) {
                if (isNaN(qty) || qty < 1) {
                    if (showError) {
                        $input.val(line.quantity);
                        Swal.fire({ icon: 'warning', title: 'Qty harus minimal 1.' });
                    }
                    return false;
                }

                if (cart === itemCart) {
                    const stock = getItemStock(line.item_id);
                    if (qty > stock) {
                        if (showError) {
                            $input.val(line.quantity);
                            Swal.fire({
                                icon: 'error',
                                title: 'Stok tidak mencukupi.',
                                text: line.name + ' — tersedia: ' + stock.toLocaleString('id-ID'),
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

            function handleQtyInput($input, showError) {
                const index = parseInt($input.data('index'), 10);
                const type = $input.data('type');
                const cart = type === 'item' ? itemCart : serviceCart;
                const line = cart[index];
                if (!line) {
                    return false;
                }

                const qty = parseInt($input.val(), 10);
                return applyQtyToLine($input, cart, line, qty, showError);
            }

            function validateItemCartStock() {
                for (let i = 0; i < itemCart.length; i++) {
                    const line = itemCart[i];
                    const stock = getItemStock(line.item_id);
                    if (line.quantity < 1) {
                        Swal.fire({ icon: 'warning', title: 'Qty barang tidak valid.', text: line.name });
                        return false;
                    }
                    if (line.quantity > stock) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Stok tidak mencukupi.',
                            text: line.name + ' — tersedia: ' + stock.toLocaleString('id-ID'),
                        });
                        return false;
                    }
                }
                return true;
            }

            function validateServiceCartQty() {
                for (let i = 0; i < serviceCart.length; i++) {
                    const line = serviceCart[i];
                    if (line.quantity < 1) {
                        Swal.fire({ icon: 'warning', title: 'Qty jasa tidak valid.', text: line.name });
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

            $servicesBody.on('input', '.cart-qty-input', function () {
                handleQtyInput($(this), false);
            });

            $servicesBody.on('blur', '.cart-qty-input', function () {
                handleQtyInput($(this), true);
            });

            $itemsBody.on('input change', '.cart-price-input', function () {
                handlePriceInput($(this));
            });

            $servicesBody.on('input change', '.cart-price-input', function () {
                handlePriceInput($(this));
            });

            $('#transaction-form').on('submit', function (e) {
                e.preventDefault();

                if (!itemCart.length && !serviceCart.length) {
                    Swal.fire({ icon: 'warning', title: 'Tambahkan minimal satu barang atau jasa.' });
                    return;
                }

                if (!validateItemCartStock() || !validateServiceCartQty()) {
                    return;
                }

                if (serviceCart.length && !$('#technician_id').val()) {
                    Swal.fire({ icon: 'warning', title: 'Pilih teknisi untuk transaksi servis.' });
                    return;
                }

                const customerMode = getCustomerMode();
                if (!customerMode) {
                    Swal.fire({ icon: 'warning', title: 'Pilih pelanggan terlebih dahulu.' });
                    return;
                }

                if (customerMode === 'new' && !$('#new_customer_name').val().trim()) {
                    Swal.fire({ icon: 'warning', title: 'Nama pelanggan baru wajib diisi.' });
                    return;
                }

                if ($('#payment_method').val() === 'transfer' && !$('#bank_account_id').val()) {
                    Swal.fire({ icon: 'warning', title: 'Pilih akun bank untuk transfer.' });
                    return;
                }

                $submit.prop('disabled', true);

                const payload = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    customer_mode: customerMode,
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

                if (customerMode === 'existing') {
                    payload.customer_id = $('#customer_id').val();
                } else if (customerMode === 'new') {
                    payload.new_customer = {
                        name: $('#new_customer_name').val().trim(),
                        phone: $('#new_customer_phone').val().trim() || null,
                        address: $('#new_customer_address').val().trim() || null,
                    };
                }

                $.ajax({
                    url: settings.storeUrl,
                    type: 'POST',
                    headers: { Accept: 'application/json' },
                    data: payload,
                    success: function (res) {
                        Swal.fire({
                            icon: 'success',
                            title: res.message,
                            text: res.data?.transaction_no || '',
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(function () {
                            window.location.href = settings.redirectUrl;
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Gagal menyimpan transaksi.' });
                        $submit.prop('disabled', false);
                        updateSummary();
                    },
                });
            });

            updateSummary();
            toggleNewCustomerFields();
            updateCustomerRemark();
            toggleItemsSectionLock();
        },
    };
})(jQuery);
