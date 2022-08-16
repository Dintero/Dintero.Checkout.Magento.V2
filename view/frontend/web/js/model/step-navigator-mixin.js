define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/payment-service',
    'Dintero_Checkout/js/action/dintero-payment-service',
    'underscore',
    'jquery'
], function (wrapper, paymentService, dinteroPaymentService, _, $) {
    'use strict';

    let mixin = {
        handleHash: function (originalFn) {
            var hashString = window.location.hash.replace('#', '');

            if (hashString === 'payment') {
                _.each(paymentService.getAvailablePaymentMethods(), function(paymentMethod) {
                    if (paymentMethod && paymentMethod.method === 'dintero' && window.checkoutConfig.payment.dintero.isEmbedded) {
                        dinteroPaymentService.init();
                    }
                })
            }
            return originalFn();
        }
    };

    return function (target) {
        return wrapper.extend(target, mixin);
    };
});
