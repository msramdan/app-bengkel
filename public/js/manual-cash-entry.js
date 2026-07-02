/**
 * Pemasukan / pengeluaran manual kas
 */
(function ($) {
    'use strict';

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    function toggleBankWrap() {
        const method = $('#entry_payment_method').val();
        const $wrap = $('#entry-bank-account-wrap');
        const $bank = $('#entry_bank_account_id');

        if (method === 'transfer') {
            $wrap.removeClass('d-none');
            $bank.prop('required', true);
        } else {
            $wrap.addClass('d-none');
            $bank.prop('required', false).val('');
        }
    }

    window.AthaManualCashEntry = {
        init: function (options) {
            const settings = $.extend({
                table: '#data-table',
                ajaxUrl: '',
                storeUrl: '',
                showUrlTemplate: '',
                cancelUrlTemplate: '',
                canCancel: false,
                entryType: 'income',
            }, options);

            const $table = $(settings.table);
            const $formModal = $('#form-modal');
            const $showModal = $('#show-modal');
            const formModal = bootstrap.Modal.getOrCreateInstance($formModal[0], { backdrop: 'static' });
            const showModal = bootstrap.Modal.getOrCreateInstance($showModal[0], { backdrop: 'static' });

            const dt = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: settings.ajaxUrl,
                    data: function (d) {
                        d.from = $('#filter-from').val();
                        d.to = $('#filter-to').val();
                        d.category_id = $('#filter-category').val();
                    },
                },
                order: [[3, 'desc']],
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'entry_no', name: 'entry_no' },
                    { data: 'amount_fmt', name: 'amount', className: 'text-end', orderable: false, searchable: false },
                    { data: 'occurred_at_fmt', name: 'occurred_at' },
                    { data: 'category_name', name: 'category.name', orderable: false },
                    { data: 'payment_label', name: 'payment_method', orderable: false, searchable: false },
                    { data: 'description_short', name: 'description', orderable: false },
                    { data: 'status_label', name: 'status', orderable: false, searchable: false },
                    { data: 'action', orderable: false, searchable: false, className: 'text-end' },
                ],
            });

            $('#btn-apply-filter').on('click', function () {
                dt.ajax.reload();
            });

            $(document).on('change', '#entry_payment_method', toggleBankWrap);

            $('[data-action="create"]').on('click', function () {
                const $form = $('#crud-form');
                $form[0].reset();
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').remove();
                $('input[name="occurred_at"]').val(new Date().toISOString().slice(0, 16));
                toggleBankWrap();
                formModal.show();
            });

            $('#crud-form').on('submit', function (e) {
                e.preventDefault();
                const $form = $(this);

                $.ajax({
                    url: settings.storeUrl,
                    type: 'POST',
                    data: $form.serialize(),
                    headers: { Accept: 'application/json' },
                    success: function (res) {
                        formModal.hide();
                        dt.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            const errors = xhr.responseJSON.errors;
                            $form.find('.is-invalid').removeClass('is-invalid');
                            $form.find('.invalid-feedback').remove();
                            $.each(errors, function (field, messages) {
                                const $input = $form.find('[name="' + field + '"]');
                                if ($input.length) {
                                    $input.addClass('is-invalid');
                                    $input.after('<div class="invalid-feedback d-block">' + messages[0] + '</div>');
                                }
                            });
                            return;
                        }
                        Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Gagal menyimpan data.' });
                    },
                });
            });

            $table.on('click', '[data-action="show-entry"]', function () {
                const id = $(this).data('id');
                const $body = $showModal.find('.modal-body');

                $body.html('<div class="tx-detail-loading"><div class="spinner-border text-danger"></div></div>');
                showModal.show();

                $.get(settings.showUrlTemplate.replace('__ID__', id), function (res) {
                    const d = res.data;
                    $('#show-entry-no').text(d.entry_no);

                    const payment = d.payment_method === 'transfer' && d.bank_account
                        ? d.payment_method + ' — ' + d.bank_account.bank_name
                        : d.payment_method;

                    $body.html(`
                        <div class="tx-detail-meta">
                            <div class="tx-detail-meta__item">
                                <span class="tx-detail-meta__icon"><i class="bi bi-tag"></i></span>
                                <div class="tx-detail-meta__text">
                                    <span class="tx-detail-meta__label">Kategori</span>
                                    <span class="tx-detail-meta__value">${d.category?.name || '-'}</span>
                                </div>
                            </div>
                            <div class="tx-detail-meta__item">
                                <span class="tx-detail-meta__icon"><i class="bi bi-cash-stack"></i></span>
                                <div class="tx-detail-meta__text">
                                    <span class="tx-detail-meta__label">Nominal</span>
                                    <span class="tx-detail-meta__value">${formatRp(d.amount)}</span>
                                </div>
                            </div>
                            <div class="tx-detail-meta__item">
                                <span class="tx-detail-meta__icon"><i class="bi bi-calendar3"></i></span>
                                <div class="tx-detail-meta__text">
                                    <span class="tx-detail-meta__label">Tanggal</span>
                                    <span class="tx-detail-meta__value">${new Date(d.occurred_at).toLocaleString('id-ID')}</span>
                                </div>
                            </div>
                            <div class="tx-detail-meta__item">
                                <span class="tx-detail-meta__icon"><i class="bi bi-wallet2"></i></span>
                                <div class="tx-detail-meta__text">
                                    <span class="tx-detail-meta__label">Metode Bayar</span>
                                    <span class="tx-detail-meta__value">${payment}</span>
                                </div>
                            </div>
                            <div class="tx-detail-meta__item">
                                <span class="tx-detail-meta__icon"><i class="bi bi-person"></i></span>
                                <div class="tx-detail-meta__text">
                                    <span class="tx-detail-meta__label">Dicatat oleh</span>
                                    <span class="tx-detail-meta__value">${d.user?.name || '-'}</span>
                                </div>
                            </div>
                            <div class="tx-detail-meta__item">
                                <span class="tx-detail-meta__icon"><i class="bi bi-flag"></i></span>
                                <div class="tx-detail-meta__text">
                                    <span class="tx-detail-meta__label">Status</span>
                                    <span class="tx-detail-meta__value">${d.status === 'completed' ? 'Aktif' : 'Dibatalkan'}</span>
                                </div>
                            </div>
                        </div>
                        ${d.description ? `<div class="tx-detail-notes mt-3"><i class="bi bi-chat-left-text"></i><span>${d.description}</span></div>` : ''}
                    `);
                });
            });

            if (settings.canCancel) {
                $table.on('click', '[data-action="cancel-entry"]', function () {
                    const id = $(this).data('id');

                    Swal.fire({
                        title: 'Batalkan entri ini?',
                        text: 'Entri tidak akan masuk laporan keuangan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, batalkan',
                        cancelButtonText: 'Tidak',
                    }).then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        $.ajax({
                            url: settings.cancelUrlTemplate.replace('__ID__', id),
                            type: 'DELETE',
                            data: { _token: $('meta[name="csrf-token"]').attr('content') },
                            headers: { Accept: 'application/json' },
                            success: function (res) {
                                dt.ajax.reload(null, false);
                                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                            },
                            error: function (xhr) {
                                Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Gagal membatalkan.' });
                            },
                        });
                    });
                });
            }
        },
    };
})(jQuery);
