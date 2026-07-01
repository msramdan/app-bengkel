(function ($) {
    'use strict';

    window.AthaPaymentFields = {
        init: function () {
            const $method = $('#payment_method');
            const $wrap = $('#bank-account-wrap');
            const $bank = $('#bank_account_id');

            function toggleBank() {
                const isTransfer = $method.val() === 'transfer';
                $wrap.toggleClass('d-none', !isTransfer);
                $bank.prop('required', isTransfer);
                if (!isTransfer) {
                    $bank.val('');
                }
            }

            $method.on('change', toggleBank);
            toggleBank();
        },
    };
})(jQuery);
