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
