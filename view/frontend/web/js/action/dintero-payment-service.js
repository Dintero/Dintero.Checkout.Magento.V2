define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'dinteroSdk',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage'
    ],
    function ($, quote, dintero, urlBuilder, storage) {
        'use strict';
        return {
            init: function() {
                const serviceUrl = urlBuilder.createUrl('/dintero/checkout/session-init', {}),
                    payload = {cartId: quote.getQuoteId()};
                try {
                    $('#dintero-embedded-checkout-container').html('');
                    storage.post(serviceUrl, JSON.stringify(payload), true, 'application/json').success(function(session) {
                        dintero.embed({
                            container: $('#dintero-embedded-checkout-container').get(0),
                            sid: session.id,
                            language: window.checkoutConfig.payment.dintero.language,
                            onPaymentError: function(event, checkout) {
                                alert($.mage.__('The payment was out of date. Refresh the page to try again'));
                                checkout.destroy();
                            }
                        });
                    });
                } catch (error) {
                    console.log(error);
                }

                return this;
            }
        };
    }
);
