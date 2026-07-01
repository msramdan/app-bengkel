/**
 * Detail transaksi di modal
 */
(function ($) {
    'use strict';

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    function typeLabel(type) {
        return { sale: 'Penjualan', service: 'Servis', combined: 'Gabungan' }[type] || type;
    }

    function paymentLabel(d) {
        const labels = { cash: 'Cash', qris: 'QRIS', transfer: 'Transfer Bank' };
        let text = labels[d.payment_method] || d.payment_method || '-';
        if (d.payment_method === 'transfer' && d.bank_account) {
            text += ' — ' + d.bank_account.bank_name;
        }
        return text;
    }

    window.AthaTransactionDetail = {
        init: function (options) {
            const settings = $.extend({
                table: '#data-table',
                showModal: '#show-modal',
                showUrl: '',
                techPercent: 80,
                ownerPercent: 20,
            }, options);

            const $table = $(settings.table);
            const $showModal = $(settings.showModal);
            const showModal = bootstrap.Modal.getOrCreateInstance($showModal[0], { backdrop: 'static' });

            $table.on('click', '[data-action="show-tx"]', function () {
                const id = $(this).data('id');
                const $body = $showModal.find('.modal-body');

                $body.html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>');
                showModal.show();

                $.get(settings.showUrl.replace('__ID__', id), function (res) {
                    const d = res.data;

                    let itemRows = (d.items || []).map(function (item) {
                        return `<tr>
                            <td>${item.item_code} — ${item.item_name}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${formatRp(item.unit_price)}</td>
                            <td class="text-end">${formatRp(item.subtotal)}</td>
                        </tr>`;
                    }).join('') || '<tr><td colspan="4" class="text-muted text-center">—</td></tr>';

                    let serviceRows = (d.service_lines || []).map(function (line) {
                        return `<tr>
                            <td>${line.service_code || '-'} — ${line.service_name}</td>
                            <td class="text-center">${line.quantity}</td>
                            <td class="text-end">${formatRp(line.unit_price)}</td>
                            <td class="text-end">${formatRp(line.subtotal)}</td>
                        </tr>`;
                    }).join('') || '<tr><td colspan="4" class="text-muted text-center">—</td></tr>';

                    $body.html(`
                        <dl class="detail-list mb-3">
                            <dt>No. Transaksi</dt><dd>${d.transaction_no}</dd>
                            <dt>Jenis</dt><dd>${typeLabel(d.type)}</dd>
                            <dt>Pelanggan</dt><dd>${d.customer?.name || '-'}</dd>
                            <dt>Teknisi</dt><dd>${d.technician?.name || '-'}</dd>
                            <dt>Kasir</dt><dd>${d.user?.name || '-'}</dd>
                            <dt>Waktu</dt><dd>${new Date(d.created_at).toLocaleString('id-ID')}</dd>
                            <dt>Metode Bayar</dt><dd>${paymentLabel(d)}</dd>
                            <dt>Catatan</dt><dd>${d.notes || '-'}</dd>
                        </dl>

                        <h6 class="fw-semibold">Barang / Sparepart</h6>
                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr><th>Barang</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr>
                                </thead>
                                <tbody>${itemRows}</tbody>
                                <tfoot><tr class="fw-medium"><td colspan="3" class="text-end">Subtotal Barang</td><td class="text-end">${formatRp(d.subtotal_items)}</td></tr></tfoot>
                            </table>
                        </div>

                        <h6 class="fw-semibold">Jasa Servis</h6>
                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr><th>Jasa</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr>
                                </thead>
                                <tbody>${serviceRows}</tbody>
                                <tfoot><tr class="fw-medium"><td colspan="3" class="text-end">Subtotal Jasa</td><td class="text-end">${formatRp(d.subtotal_services)}</td></tr></tfoot>
                            </table>
                        </div>

                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between mb-1"><span>Diskon</span><span class="text-danger">- ${formatRp(d.discount)}</span></div>
                            <div class="d-flex justify-content-between fw-bold fs-5 mb-2"><span>Total</span><span>${formatRp(d.total)}</span></div>
                            <hr>
                            <div class="small">
                                <div class="fw-semibold mb-1">Pembagian (Teknisi ${settings.techPercent}% jasa · Owner ${settings.ownerPercent}% jasa · Sparepart 100% toko)</div>
                                <div class="d-flex justify-content-between"><span>Komisi teknisi</span><span>${formatRp(d.technician_commission)}</span></div>
                                <div class="d-flex justify-content-between"><span>Bagian owner (jasa)</span><span>${formatRp(d.owner_service_share)}</span></div>
                                <div class="d-flex justify-content-between"><span>Bagian owner (sparepart)</span><span>${formatRp(d.owner_items_share)}</span></div>
                                <div class="d-flex justify-content-between fw-semibold mt-1"><span>Total owner</span><span>${formatRp(d.owner_total_share)}</span></div>
                            </div>
                        </div>
                    `);
                }).fail(function () {
                    $body.html('<p class="text-danger mb-0">Gagal memuat detail.</p>');
                });
            });
        },
    };
})(jQuery);
