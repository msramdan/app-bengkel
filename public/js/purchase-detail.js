(function ($) {
    'use strict';

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    function paymentLabel(d) {
        const labels = { cash: 'Cash', qris: 'QRIS', transfer: 'Transfer Bank' };
        let text = labels[d.payment_method] || d.payment_method || '-';
        if (d.payment_method === 'transfer' && d.bank_account) {
            text += ' — ' + d.bank_account.bank_name + ' (' + d.bank_account.account_number + ')';
        }
        return text;
    }

    window.AthaPurchaseDetail = {
        init: function (options) {
            const settings = $.extend({
                table: '#data-table',
                showModal: '#show-modal',
                showUrl: '',
                cancelUrlTemplate: '',
                canCancel: false,
            }, options);

            const $table = $(settings.table);
            const $showModal = $(settings.showModal);
            const showModal = bootstrap.Modal.getOrCreateInstance($showModal[0]);
            const dataTable = $table.DataTable ? $table.DataTable() : null;

            if (settings.canCancel && settings.cancelUrlTemplate) {
                $table.on('click', '[data-action="cancel-purchase"]', function () {
                    const id = $(this).data('id');
                    const no = $(this).data('no') || id;

                    Swal.fire({
                        title: 'Batalkan pembelian?',
                        html: 'Pembelian <strong>' + escapeHtml(no) + '</strong> akan dibatalkan.<br>Stok dikurangi kembali dan pengeluaran dihapus dari laporan keuangan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, batalkan',
                        cancelButtonText: 'Tidak',
                        confirmButtonColor: '#dc3545',
                    }).then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        $.ajax({
                            url: settings.cancelUrlTemplate.replace('__ID__', id),
                            type: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            },
                            data: { _method: 'DELETE' },
                            success: function (res) {
                                Swal.fire({
                                    icon: 'success',
                                    title: res.message || 'Pembelian dibatalkan.',
                                    timer: 1600,
                                    showConfirmButton: false,
                                });
                                if (dataTable) {
                                    dataTable.ajax.reload(null, false);
                                }
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: xhr.responseJSON?.message || 'Gagal membatalkan pembelian.',
                                });
                            },
                        });
                    });
                });
            }

            $table.on('click', '[data-action="show-purchase"]', function () {
                const id = $(this).data('id');
                const $body = $showModal.find('.modal-body');

                $body.html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>');
                showModal.show();

                $.get(settings.showUrl.replace('__ID__', id), function (res) {
                    const d = res.data;
                    const rows = (d.items || []).map(function (item) {
                        return `<tr>
                            <td>${item.item_code} — ${item.item_name}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">${formatRp(item.unit_price)}</td>
                            <td class="text-end">${formatRp(item.subtotal)}</td>
                        </tr>`;
                    }).join('');

                    const statusBadge = d.status === 'cancelled'
                        ? '<span class="badge bg-danger-subtle text-danger">Batal</span>'
                        : '<span class="badge bg-success-subtle text-success">Selesai</span>';

                    $body.html(`
                        <dl class="detail-list mb-3">
                            <dt>No. Pembelian</dt><dd>${d.purchase_no}</dd>
                            <dt>Status</dt><dd>${statusBadge}</dd>
                            <dt>Supplier</dt><dd>${d.supplier_name || '-'}</dd>
                            <dt>Petugas</dt><dd>${d.user?.name || '-'}</dd>
                            <dt>Waktu</dt><dd>${new Date(d.created_at).toLocaleString('id-ID')}</dd>
                            <dt>Metode Bayar</dt><dd>${paymentLabel(d)}</dd>
                            <dt>Catatan</dt><dd>${d.notes || '-'}</dd>
                            ${d.status === 'cancelled' ? `<dt>Dibatalkan</dt><dd>${d.cancelled_at ? new Date(d.cancelled_at).toLocaleString('id-ID') : '-'}${d.cancelled_by_user ? ' oleh ' + d.cancelled_by_user.name : ''}</dd>` : ''}
                        </dl>
                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr><th>Barang</th><th class="text-center">Qty</th><th class="text-end">Harga Beli</th><th class="text-end">Subtotal</th></tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between"><span>Subtotal</span><span>${formatRp(d.subtotal)}</span></div>
                            <div class="d-flex justify-content-between"><span>Diskon</span><span class="text-danger">- ${formatRp(d.discount)}</span></div>
                            <div class="d-flex justify-content-between fw-bold fs-5"><span>Total Pengeluaran</span><span class="text-warning">${formatRp(d.total)}</span></div>
                        </div>
                        <p class="form-hint-sm mt-2 mb-0"><i class="bi bi-box-arrow-in-down me-1"></i> Stok masuk otomatis — cek menu Stok Masuk (sumber: Pembelian Barang).</p>
                    `);
                }).fail(function () {
                    $body.html('<p class="text-danger mb-0">Gagal memuat detail.</p>');
                });
            });
        },
    };
})(jQuery);
