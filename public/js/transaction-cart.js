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
                const $selected = $('#technician_id option:selected');
                const fromTech = parseFloat($selected.data('commission-percent'));
                if ($selected.val() && !isNaN(fromTech)) {
                    return fromTech;
                }
                return settings.defaultTechPercent;
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
                                <td class="text-center">${line.quantity}</td>
                                <td class="text-end">${formatRp(line.unit_price)}</td>
                                <td class="text-end fw-medium">${formatRp(line.subtotal)}</td>
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
                                <td class="text-center">${line.quantity}</td>
                                <td class="text-end">${formatRp(line.unit_price)}</td>
                                <td class="text-end fw-medium">${formatRp(line.subtotal)}</td>
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
                const hasCustomer = !!$('#customer_id').val();
                const needsTech = serviceCart.length > 0;
                const hasTech = !!$('#technician_id').val();
                const paymentOk = $('#payment_method').val() !== 'transfer' || !!$('#bank_account_id').val();

                $submit.prop('disabled', !(hasLines && hasCustomer && (!needsTech || hasTech) && paymentOk));
            }

            $('#customer_id, #technician_id, #discount, #payment_method, #bank_account_id').on('change input', updateSummary);

            $('#item_select').on('change', function () {
                const item = findItem($(this).val());
                $('#item-hint').text(item ? 'Stok: ' + Number(item.stock).toLocaleString('id-ID') + ' | Harga: ' + formatRp(item.selling_price) : '');
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

                const existing = itemCart.find(function (l) { return String(l.item_id) === String(itemId); });
                const newQty = (existing ? existing.quantity : 0) + qty;

                if (newQty > item.stock) {
                    Swal.fire({ icon: 'error', title: 'Stok tidak mencukupi.', text: 'Tersedia: ' + Number(item.stock).toLocaleString('id-ID') });
                    return;
                }

                const unitPrice = parseFloat(item.selling_price);
                if (existing) {
                    existing.quantity = newQty;
                    existing.subtotal = unitPrice * newQty;
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
                $('#item_select').val('').trigger('change');
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
                    existing.subtotal = unitPrice * newQty;
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
                $('#service_select').val('');
            });

            $itemsBody.on('click', '.btn-remove-item', function () {
                itemCart.splice($(this).data('index'), 1);
                renderItemCart();
            });

            $servicesBody.on('click', '.btn-remove-service', function () {
                serviceCart.splice($(this).data('index'), 1);
                renderServiceCart();
            });

            $('#transaction-form').on('submit', function (e) {
                e.preventDefault();

                if (!itemCart.length && !serviceCart.length) {
                    Swal.fire({ icon: 'warning', title: 'Tambahkan minimal satu barang atau jasa.' });
                    return;
                }

                if (serviceCart.length && !$('#technician_id').val()) {
                    Swal.fire({ icon: 'warning', title: 'Pilih teknisi untuk transaksi servis.' });
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
                        customer_id: $('#customer_id').val(),
                        technician_id: $('#technician_id').val() || null,
                        discount: $('#discount').val() || 0,
                        notes: $('#notes').val(),
                        payment_method: $('#payment_method').val(),
                        bank_account_id: $('#bank_account_id').val() || null,
                        items: itemCart.map(function (l) {
                            return { item_id: l.item_id, quantity: l.quantity };
                        }),
                        services: serviceCart.map(function (l) {
                            return { workshop_service_id: l.workshop_service_id, quantity: l.quantity };
                        }),
                    },
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
        },
    };
})(jQuery);
