/**
 * Dropdown dengan pencarian (Select2) — barang, pelanggan, teknisi, dll.
 */
(function ($) {
    'use strict';

    const LANG = {
        noResults: function () { return 'Tidak ditemukan'; },
        searching: function () { return 'Mencari...'; },
        inputTooShort: function () { return 'Ketik untuk mencari'; },
        loadingMore: function () { return 'Memuat...'; },
    };

    function placeholderFor($el) {
        const $empty = $el.find('option[value=""]').first();
        return $empty.length ? $empty.text() : 'Pilih...';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function isMemberOption(option) {
        if (!option.element) {
            return false;
        }

        const $el = $(option.element);
        const raw = $el.data('isMember');

        if (raw !== undefined) {
            return String(raw) === '1' || raw === true;
        }

        return $el.attr('data-is-member') === '1';
    }

    function customerCardIcon(option, extraClass) {
        const isMember = isMemberOption(option);
        const tone = isMember ? 'member' : 'regular';
        const extra = extraClass ? ' ' + extraClass : '';

        return '<i class="bi bi-person-vcard atha-s2-opt-card atha-s2-opt-card--' + tone + extra + '"></i>';
    }

    function formatCustomerOption(option) {
        if (!option.id) {
            return option.text;
        }

        const val = String(option.id);
        const text = option.text || '';

        if (val === '__new__') {
            return $('<span class="atha-s2-opt atha-s2-opt--action"><i class="bi bi-person-plus"></i> Pelanggan baru</span>');
        }

        if (val === '__umum__') {
            return $('<span class="atha-s2-opt atha-s2-opt--walkin"><i class="bi bi-shop"></i> ' + escapeHtml(text) + '</span>');
        }

        const sep = text.indexOf(' — ');
        if (sep > -1) {
            const code = text.slice(0, sep);
            const name = text.slice(sep + 3);
            return $('<span class="atha-s2-opt">' + customerCardIcon(option) + '<span class="atha-s2-opt-code">' + escapeHtml(code) + '</span><span class="atha-s2-opt-sep">—</span><span class="atha-s2-opt-name">' + escapeHtml(name) + '</span></span>');
        }

        return $('<span class="atha-s2-opt">' + customerCardIcon(option) + escapeHtml(text) + '</span>');
    }

    function formatCustomerSelection(option) {
        if (!option.id) {
            return option.text;
        }

        const val = String(option.id);
        const text = option.text || '';

        if (val === '__new__' || val === '__umum__') {
            return text;
        }

        const sep = text.indexOf(' — ');
        const label = sep > -1 ? text.slice(sep + 3) : text;

        return $('<span class="atha-s2-opt atha-s2-opt--selection">' + customerCardIcon(option) + '<span class="atha-s2-opt-name">' + escapeHtml(label) + '</span></span>');
    }

    window.AthaSearchableSelect = {
        init: function (selector, options) {
            if (!$.fn.select2) {
                return;
            }

            const defaults = {
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                minimumResultsForSearch: 0,
                language: LANG,
                containerCssClass: 'atha-select2-wrap',
                selectionCssClass: 'atha-select2-selection',
                dropdownCssClass: 'atha-select2-dropdown',
            };

            $(selector).each(function () {
                const $el = $(this);
                if ($el.data('select2')) {
                    return;
                }

                const opts = $.extend({}, defaults, options || {}, {
                    placeholder: placeholderFor($el),
                });

                if ($el.hasClass('atha-select2-customer')) {
                    opts.templateResult = formatCustomerOption;
                    opts.templateSelection = formatCustomerSelection;
                    opts.dropdownAutoWidth = false;
                }

                const parent = $el.data('searchable-parent');
                if (parent) {
                    opts.dropdownParent = $(parent);
                }

                $el.select2(opts);
            });
        },

        clear: function (selector) {
            $(selector).val(null).trigger('change');
        },

        destroy: function (selector) {
            $(selector).each(function () {
                const $el = $(this);
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
            });
        },
    };

    function boot() {
        AthaSearchableSelect.init('.atha-searchable-select');
    }

    if (document.readyState === 'loading') {
        $(boot);
    } else {
        boot();
    }
})(jQuery);
