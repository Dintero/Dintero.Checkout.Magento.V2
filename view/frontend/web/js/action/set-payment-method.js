define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/set-payment-information'
], function (quote, setPaymentInformation) {
    'use strict';

    return function (messageContainer) {
        var pm = quote.paymentMethod() || {};
        return setPaymentInformation(messageContainer, {
            method: pm.method,
            po_number: pm.po_number || null,
            additional_data: pm.additional_data || null
        });
    };
});
