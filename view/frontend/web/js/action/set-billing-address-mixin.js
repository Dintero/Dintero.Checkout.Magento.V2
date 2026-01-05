define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setBillingAddressAction) {
        return wrapper.wrap(setBillingAddressAction, function (originalAction, messageContainer) {
            return originalAction(messageContainer)
                .done(function () {
                    $(document).trigger('dintero_billing_address_update_complete');
                });
        });
    };
});
