/**
 * Atha Motor — Modal CRUD helper (Bootstrap 5 + DataTables + SweetAlert2)
 */
(function ($) {
    'use strict';

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function clearFormErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
    }

    function showFormErrors($form, errors) {
        clearFormErrors($form);
        $.each(errors, function (field, messages) {
            const $input = $form.find('[name="' + field + '"]');
            if (!$input.length) {
                return;
            }
            $input.addClass('is-invalid');
            $input.after('<div class="invalid-feedback d-block">' + messages[0] + '</div>');
        });
    }

    function fillForm($form, data) {
        $form[0].reset();
        $.each(data, function (key, value) {
            const $field = $form.find('[name="' + key + '"]');
            if (!$field.length) {
                return;
            }
            if ($field.attr('type') === 'checkbox') {
                $field.prop('checked', !!value);
            } else if ($field.attr('type') !== 'file') {
                $field.val(value);
            }
        });
    }

    function formUsesMultipart($form) {
        return ($form.attr('enctype') || '').toLowerCase() === 'multipart/form-data';
    }

    function buildSubmitData($form) {
        if (!formUsesMultipart($form)) {
            return { payload: $form.serialize(), options: {} };
        }

        return {
            payload: new FormData($form[0]),
            options: { processData: false, contentType: false },
        };
    }

    window.AthaEntityPhoto = {
        reset: function () {
            $('#entity-photo-input').val('');
            $('#entity-remove-photo-wrap').addClass('d-none');
            $('#entity-remove-photo').prop('checked', false);
            this.setPreview(null);
        },
        setPreview: function (url) {
            const $img = $('#entity-photo-img');
            const $placeholder = $('#entity-photo-placeholder');

            if (url) {
                $img.attr('src', url).removeClass('d-none');
                $placeholder.addClass('d-none');
                $('#entity-remove-photo-wrap').removeClass('d-none');
            } else {
                $img.attr('src', '').addClass('d-none');
                $placeholder.removeClass('d-none');
                $('#entity-remove-photo-wrap').addClass('d-none');
            }
        },
        bind: function () {
            $(document).on('change', '#entity-photo-input', function () {
                const file = this.files[0];
                if (!file) {
                    return;
                }
                AthaEntityPhoto.setPreview(URL.createObjectURL(file));
                $('#entity-remove-photo').prop('checked', false);
            });

            $(document).on('change', '#entity-remove-photo', function () {
                if (this.checked) {
                    $('#entity-photo-input').val('');
                }
            });
        },
    };

    AthaEntityPhoto.bind();

    window.AthaModalCrud = {
        init: function (options) {
            const settings = $.extend({
                table: '#data-table',
                formModal: '#form-modal',
                showModal: '#show-modal',
                form: '#crud-form',
                storeUrl: '',
                updateUrlTemplate: '',
                showUrlTemplate: '',
                destroyUrlTemplate: '',
                entityName: 'Data',
                renderShow: null,
                onFormReset: null,
            }, options);

            const $table = $(settings.table);
            const $formModal = $(settings.formModal);
            const $showModal = $(settings.showModal);
            const $form = $(settings.form);
            const formModal = bootstrap.Modal.getOrCreateInstance($formModal[0], { backdrop: 'static' });
            const showModal = bootstrap.Modal.getOrCreateInstance($showModal[0], { backdrop: 'static' });

            function reloadTable() {
                $table.DataTable().ajax.reload(null, false);
            }

            function toastSuccess(message) {
                Swal.fire({
                    icon: 'success',
                    title: message,
                    timer: 1600,
                    showConfirmButton: false,
                });
            }

            function toastError(message) {
                Swal.fire({
                    icon: 'error',
                    title: message || 'Terjadi kesalahan.',
                });
            }

            $(document).on('click', '[data-action="create"]', function () {
                clearFormErrors($form);
                $form[0].reset();
                $form.find('[name="_method"]').remove();
                $form.attr('action', settings.storeUrl);
                $formModal.find('[data-modal-title]').text('Tambah ' + settings.entityName);
                if (window.AthaEntityPhoto) {
                    AthaEntityPhoto.reset();
                }
                if (typeof settings.onFormReset === 'function') {
                    settings.onFormReset('create');
                }
                formModal.show();
            });

            $table.on('click', '[data-action="edit"]', function () {
                const id = $(this).data('id');
                clearFormErrors($form);
                $.get(settings.showUrlTemplate.replace('__ID__', id), function (res) {
                    $form.find('[name="_method"]').remove();
                    $form.append('<input type="hidden" name="_method" value="PUT">');
                    $form.attr('action', settings.updateUrlTemplate.replace('__ID__', id));
                    $formModal.find('[data-modal-title]').text('Edit ' + settings.entityName);
                    fillForm($form, res.data);
                    if (window.AthaEntityPhoto) {
                        AthaEntityPhoto.setPreview(res.data.photo_url || null);
                    }
                    if (typeof settings.onFormReset === 'function') {
                        settings.onFormReset('edit', res.data);
                    }
                    formModal.show();
                }).fail(function () {
                    toastError('Gagal memuat data.');
                });
            });

            $table.on('click', '[data-action="show"]', function () {
                const id = $(this).data('id');
                const $body = $showModal.find('.modal-body');
                $body.html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm"></div></div>');
                showModal.show();

                $.get(settings.showUrlTemplate.replace('__ID__', id), function (res) {
                    if (typeof settings.renderShow === 'function') {
                        $body.html(settings.renderShow(res.data));
                    }
                }).fail(function () {
                    $body.html('<p class="text-danger mb-0">Gagal memuat detail.</p>');
                });
            });

            $table.on('click', '[data-action="delete"]', function () {
                const id = $(this).data('id');
                const stock = Number($(this).data('stock'));
                const confirmText = Number.isFinite(stock) && stock > 0
                    ? 'Barang masih ada stok ' + stock + '. Tetap hapus paksa? Riwayat transaksi lama tidak ikut terhapus.'
                    : 'Data yang dihapus tidak dapat dikembalikan.';
                Swal.fire({
                    title: 'Hapus ' + settings.entityName + '?',
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#8B1538',
                    cancelButtonText: 'Batal',
                    confirmButtonText: 'Ya, hapus',
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }
                    $.ajax({
                        url: settings.destroyUrlTemplate.replace('__ID__', id),
                        type: 'POST',
                        data: { _method: 'DELETE', _token: csrfToken() },
                        headers: { Accept: 'application/json' },
                        success: function (res) {
                            toastSuccess(res.message);
                            reloadTable();
                        },
                        error: function (xhr) {
                            toastError(xhr.responseJSON?.message);
                        },
                    });
                });
            });

            $form.on('submit', function (e) {
                e.preventDefault();
                clearFormErrors($form);
                const $btn = $form.find('[type="submit"]');
                $btn.prop('disabled', true);

                const submitData = buildSubmitData($form);

                $.ajax($.extend({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: submitData.payload,
                    headers: { Accept: 'application/json' },
                    success: function (res) {
                        formModal.hide();
                        toastSuccess(res.message);
                        reloadTable();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            showFormErrors($form, xhr.responseJSON.errors);
                        } else {
                            toastError(xhr.responseJSON?.message);
                        }
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    },
                }, submitData.options));
            });
        },
    };
})(jQuery);
