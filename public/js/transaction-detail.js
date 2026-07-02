/**
 * Detail transaksi di modal
 */
(function ($) {
    'use strict';

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function typeLabel(type) {
        return { sale: 'Penjualan', service: 'Servis', combined: 'Gabungan' }[type] || type;
    }

    function typeBadge(type) {
        const map = {
            sale: 'tx-detail-badge tx-detail-badge--sale',
            service: 'tx-detail-badge tx-detail-badge--service',
            combined: 'tx-detail-badge tx-detail-badge--combined',
        };

        return `<span class="${map[type] || 'tx-detail-badge'}">${escapeHtml(typeLabel(type))}</span>`;
    }

    function statusBadge(status) {
        if (status === 'held') {
            return '<span class="tx-detail-badge tx-detail-badge--held">Open Order</span>';
        }
        if (status === 'cancelled') {
            return '<span class="tx-detail-badge tx-detail-badge--cancelled">Batal</span>';
        }
        return '<span class="tx-detail-badge tx-detail-badge--done">Selesai</span>';
    }

    function paymentLabel(d) {
        const labels = { cash: 'Cash', qris: 'QRIS', transfer: 'Transfer Bank' };
        let text = labels[d.payment_method] || d.payment_method || '-';
        if (d.payment_method === 'transfer' && d.bank_account) {
            text += ' — ' + d.bank_account.bank_name;
        }
        return text;
    }

    function customerLabel(d) {
        const name = d.customer_name || d.customer?.name || '-';
        const isMember = d.customer?.is_member;

        if (!d.customer || isMember === undefined || isMember === null) {
            return escapeHtml(name);
        }

        const iconClass = isMember ? 'tx-detail-member-icon--member' : 'tx-detail-member-icon--regular';
        const memberTag = isMember
            ? '<span class="tx-detail-member-tag">Member</span>'
            : '';

        return `<span class="tx-detail-customer"><i class="bi bi-person-vcard tx-detail-member-icon ${iconClass}"></i><span>${escapeHtml(name)}</span>${memberTag}</span>`;
    }

    function metaItem(icon, label, value) {
        return `
            <div class="tx-detail-meta__item">
                <span class="tx-detail-meta__icon"><i class="bi ${icon}"></i></span>
                <div class="tx-detail-meta__text">
                    <span class="tx-detail-meta__label">${label}</span>
                    <span class="tx-detail-meta__value">${value}</span>
                </div>
            </div>
        `;
    }

    function renderTableSection(title, icon, headLabel, rowsHtml, subtotalLabel, subtotal) {
        return `
            <section class="tx-detail-section">
                <div class="tx-detail-section__head">
                    <h6 class="tx-detail-section__title"><i class="bi ${icon}"></i> ${title}</h6>
                </div>
                <div class="tx-detail-table-wrap">
                    <table class="table table-sm tx-detail-table mb-0">
                        <thead>
                            <tr>
                                <th>${headLabel}</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end">${subtotalLabel}</td>
                                <td class="text-end">${formatRp(subtotal)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        `;
    }

    window.AthaTransactionDetail = {
        init: function (options) {
            const settings = $.extend({
                table: '#data-table',
                showModal: '#show-modal',
                showUrl: '',
                invoiceUrl: '',
                techPercent: 80,
                ownerPercent: 20,
            }, options);

            const $table = $(settings.table);
            const $showModal = $(settings.showModal);
            const showModal = bootstrap.Modal.getOrCreateInstance($showModal[0], { backdrop: 'static' });

            $table.on('click', '[data-action="show-tx"]', function () {
                const id = $(this).data('id');
                const $body = $showModal.find('.modal-body');
                const $title = $('#tx-detail-modal-title');
                const $printBtn = $('#btn-print-invoice');

                $printBtn.addClass('d-none').attr('href', '#');
                $title.text('Memuat...');
                $body.html('<div class="tx-detail-loading"><div class="spinner-border text-danger" role="status"></div><span>Memuat detail transaksi...</span></div>');
                showModal.show();

                $.get(settings.showUrl.replace('__ID__', id), function (res) {
                    const d = res.data;

                    if (settings.invoiceUrl) {
                        $printBtn
                            .attr('href', settings.invoiceUrl.replace('__ID__', id))
                            .removeClass('d-none');
                    }

                    $title.text(d.transaction_no);

                    const itemRows = (d.items || []).map(function (item) {
                        return `<tr>
                            <td><span class="tx-detail-line-code">${escapeHtml(item.item_code)}</span><span class="tx-detail-line-name">${escapeHtml(item.item_name)}</span></td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${formatRp(item.unit_price)}</td>
                            <td class="text-end fw-medium">${formatRp(item.subtotal)}</td>
                        </tr>`;
                    }).join('') || '<tr><td colspan="4" class="tx-detail-empty">Tidak ada barang</td></tr>';

                    const serviceRows = (d.service_lines || []).map(function (line) {
                        return `<tr>
                            <td><span class="tx-detail-line-code">${escapeHtml(line.service_code || '-')}</span><span class="tx-detail-line-name">${escapeHtml(line.service_name)}</span></td>
                            <td class="text-center">${line.quantity}</td>
                            <td class="text-end">${formatRp(line.unit_price)}</td>
                            <td class="text-end fw-medium">${formatRp(line.subtotal)}</td>
                        </tr>`;
                    }).join('') || '<tr><td colspan="4" class="tx-detail-empty">Tidak ada jasa</td></tr>';

                    const notes = d.notes
                        ? `<div class="tx-detail-notes"><i class="bi bi-chat-left-text"></i><span>${escapeHtml(d.notes)}</span></div>`
                        : '';

                    $body.html(`
                        <div class="tx-detail-hero">
                            <div class="tx-detail-hero__main">
                                <div class="tx-detail-hero__badges">
                                    ${typeBadge(d.type)}
                                    ${statusBadge(d.status)}
                                </div>
                                <div class="tx-detail-hero__total-label">Total Transaksi</div>
                                <div class="tx-detail-hero__total">${formatRp(d.total)}</div>
                            </div>
                        </div>

                        <div class="tx-detail-meta">
                            ${metaItem('bi-person', 'Pelanggan', customerLabel(d))}
                            ${metaItem('bi-wrench-adjustable', 'Teknisi', escapeHtml(d.technician?.name || '-'))}
                            ${metaItem('bi-person-badge', 'Kasir', escapeHtml(d.user?.name || '-'))}
                            ${metaItem('bi-wallet2', 'Metode Bayar', escapeHtml(paymentLabel(d)))}
                            ${metaItem('bi-calendar3', 'Waktu', escapeHtml(new Date(d.created_at).toLocaleString('id-ID')))}
                        </div>

                        ${notes}

                        ${renderTableSection('Barang / Sparepart', 'bi-box-seam', 'Barang', itemRows, 'Subtotal Barang', d.subtotal_items)}
                        ${renderTableSection('Jasa Servis', 'bi-tools', 'Jasa', serviceRows, 'Subtotal Jasa', d.subtotal_services)}

                        <section class="tx-detail-summary">
                            <div class="tx-detail-summary__row">
                                <span>Subtotal Barang</span>
                                <span>${formatRp(d.subtotal_items)}</span>
                            </div>
                            <div class="tx-detail-summary__row">
                                <span>Subtotal Jasa</span>
                                <span>${formatRp(d.subtotal_services)}</span>
                            </div>
                            <div class="tx-detail-summary__row tx-detail-summary__row--discount">
                                <span>Diskon</span>
                                <span>- ${formatRp(d.discount)}</span>
                            </div>
                            <div class="tx-detail-summary__row tx-detail-summary__row--total">
                                <span>Total</span>
                                <span>${formatRp(d.total)}</span>
                            </div>
                        </section>

                        <section class="tx-detail-commission">
                            <div class="tx-detail-commission__title">
                                <i class="bi bi-pie-chart"></i>
                                Pembagian hasil
                            </div>
                            <div class="tx-detail-commission__hint">
                                Teknisi ${settings.techPercent}% jasa · Owner ${settings.ownerPercent}% jasa · Sparepart 100% toko
                            </div>
                            <div class="tx-detail-commission__row">
                                <span>Komisi teknisi</span>
                                <span>${formatRp(d.technician_commission)}</span>
                            </div>
                            <div class="tx-detail-commission__row">
                                <span>Bagian owner (jasa)</span>
                                <span>${formatRp(d.owner_service_share)}</span>
                            </div>
                            <div class="tx-detail-commission__row">
                                <span>Bagian owner (sparepart)</span>
                                <span>${formatRp(d.owner_items_share)}</span>
                            </div>
                            <div class="tx-detail-commission__row tx-detail-commission__row--total">
                                <span>Total owner</span>
                                <span>${formatRp(d.owner_total_share)}</span>
                            </div>
                        </section>
                    `);
                }).fail(function () {
                    $title.text('Detail Transaksi');
                    $body.html('<div class="tx-detail-error"><i class="bi bi-exclamation-circle"></i> Gagal memuat detail transaksi.</div>');
                });
            });
        },
    };
})(jQuery);
