define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'dinteroSdk',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'uiLayout',
        'Magento_SalesRule/js/action/set-coupon-code',
        'Magento_SalesRule/js/action/cancel-coupon',
    ],
    function ($, quote, dintero, urlBuilder, storage, layout, setCoupon, cancelCoupon) {
        'use strict';

        var checkoutInstance;

        const refreshSession = function() {
            if (checkoutInstance) {
                checkoutInstance.refreshSession();
            }
        }

        const lockSession = function() {
            if (checkoutInstance) {
                checkoutInstance.lockSession();
            }
        }

        setCoupon.registerSuccessCallback(refreshSession);
        setCoupon.registerFailCallback(refreshSession);
        setCoupon.registerDataModifier(lockSession);

        cancelCoupon.registerSuccessCallback(refreshSession);
        $(document).on('coupon_cancel_before', lockSession)

        return {
            currentRequest: false,
            init: function(containerSelector = '#dintero-embedded-checkout-container') {
                var _this = this;
                if (!window.checkoutConfig.payment.dintero.isEmbedded) {
                    return this;
                }

                const serviceUrl = urlBuilder.createUrl('/dintero/checkout/session-init', {}),
                    payload = {cartId: quote.getQuoteId()};
                try {
                    $(containerSelector).html('');
                    if (_this.currentRequest) {
                        _this.currentRequest.abort();
                        _this.currentRequest = false;
                    }
                    storage.post(serviceUrl, JSON.stringify(payload), true, 'application/json')
                        .then(function(session, status, request) {
                            _this.currentRequest = request;
                            $(containerSelector).html('');
                            dintero.embed({
                                container: $(containerSelector).get(0),
                                popOut: window.checkoutConfig.payment.dintero.isPopout,
                                sid: session.id,
                                language: window.checkoutConfig.payment.dintero.language,
                                onPaymentError: function(event, checkout) {
                                    $(_this).trigger(
                                        'dintero.payment.failed',
                                        $.mage.__('The payment was out of date. Refresh the page to try again')
                                    );
                                    checkout.destroy();
                                },
                                onSessionLocked: function(event, checkout, callback) {
                                    console.log(`Session type ${event.type}`);
                                    const serviceUrl = urlBuilder.createUrl('/dintero/checkout/session-update', {});
                                    storage.post(serviceUrl, {}, true, 'application/json').done(() => {
                                        refreshSession();
                                    });
                                },
                                onSessionLockFailed: function(event, checkout) {
                                    console.log('Session log failed');
                                }
                            }).then(function(checkout) {
                                checkoutInstance = checkout;
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
