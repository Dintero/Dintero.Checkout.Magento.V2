define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'dinteroSdk',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'uiLayout'
    ],
    function ($, quote, dintero, urlBuilder, storage, layout) {
        'use strict';
        return {
            currentRequest: false,
            init: async function() {
                var _this = this;
                if (!window.checkoutConfig.payment.dintero.isEmbedded) {
                    return this;
                }

                const serviceUrl = urlBuilder.createUrl('/dintero/checkout/session-init', {}),
                    payload = {cartId: quote.getQuoteId()};
                try {
                    $('#dintero-embedded-checkout-container').html('');
                    if (_this.currentRequest) {
                        _this.currentRequest.abort();
                        _this.currentRequest = false;
                    }
                    _this.currentRequest = await storage.post(serviceUrl, JSON.stringify(payload), true, 'application/json')
                        .then(function(session) {
                            dintero.embed({
                                container: $('#dintero-embedded-checkout-container').get(0),
                                sid: session.id,
                                language: window.checkoutConfig.payment.dintero.language,
                                onPaymentError: function(event, checkout) {
                                    $(_this).trigger(
                                        'dintero.payment.failed',
                                        $.mage.__('The payment was out of date. Refresh the page to try again')
                                    );
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
