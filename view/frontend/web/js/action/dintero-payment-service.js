define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'dinteroSdk',
        'uiLayout',
        'Magento_SalesRule/js/action/set-coupon-code',
        'Magento_SalesRule/js/action/cancel-coupon',
        'Magento_Checkout/js/action/get-totals',
        'Dintero_Checkout/js/action/session-management-service'
    ],
    function ($, quote, dintero, layout, setCoupon, cancelCoupon, getTotalsAction, sessionManagementService) {
        'use strict';

        const sessionManager = new sessionManagementService();

        var checkoutInstance;
        var processingUpdateFlag = false;

        const refreshSession = function() {
            sessionManager.updateSession().done(() => {
                if (checkoutInstance) {
                    checkoutInstance.refreshSession();
                }
            });
        }

        const updateSession = function() {
            if (checkoutInstance && !processingUpdateFlag) {
                checkoutInstance.lockSession().then(() => {
                    refreshSession();
                    processingUpdateFlag = false;
                });
            }
        }

        setCoupon.registerSuccessCallback(refreshSession);
        setCoupon.registerFailCallback(refreshSession);
        setCoupon.registerDataModifier(updateSession);

        cancelCoupon.registerSuccessCallback(refreshSession);
        $(document).on('coupon_cancel_before', updateSession)

        return {
            currentRequest: false,
            init: function(containerSelector = '#dintero-embedded-checkout-container') {
                var _this = this;
                if (!window.checkoutConfig.payment.dintero.isEmbedded) {
                    return this;
                }

                try {

                    $(containerSelector).html('');
                    if (_this.currentRequest) {
                        _this.currentRequest.abort();
                        _this.currentRequest = false;
                    }

                    sessionManager.initSession(quote.getQuoteId())
                        .then(function(session, status, request) {
                            _this.currentRequest = request;
                            $(containerSelector).html('');
                            dintero.embed({
                                container: $(containerSelector).get(0),
                                popOut: window.checkoutConfig.payment.dintero.isPopout,
                                sid: session.id,
                                language: window.checkoutConfig.payment.dintero.language,

                                /**
                                 * Payment error handler
                                 *
                                 * @param event
                                 * @param checkout
                                 */
                                onPaymentError: function(event, checkout) {
                                    $(_this).trigger(
                                        'dintero.payment.failed',
                                        $.mage.__('The payment was out of date. Refresh the page to try again')
                                    );
                                    checkout.destroy();
                                },

                                /**
                                 * Session updates event handler
                                 *
                                 * @param event
                                 * @param checkout
                                 */
                                onSession: function(event, checkout) {
                                    sessionManager.updateTotals(checkout?.session?.id).done(() => {
                                        const shippingMethod = checkout?.session?.order?.shipping_option;
                                        if (shippingMethod && shippingMethod.id) {
                                            quote.shippingMethod({
                                                method_title: shippingMethod.title,
                                                carrier_title: shippingMethod.operator,
                                                method_code: shippingMethod.id
                                            });
                                        }
                                        getTotalsAction([]);
                                    });
                                },

                                /**
                                 * Session locked event handler
                                 *
                                 * @param event
                                 * @param checkout
                                 */
                                onSessionLockFailed: function(event, checkout) {
                                    console.log('Session lock failed');
                                    processingUpdateFlag = false;
                                },

                                /**
                                 * Session validation event handler
                                 *
                                 * @param event
                                 * @param checkout
                                 * @param callback
                                 */
                                onValidateSession: function(event, checkout, callback) {
                                    sessionManager.validateSession(checkout?.session?.id).done((isValid) => {
                                        const result = {success: isValid};
                                        if (!isValid) {
                                            updateSession();
                                            result.clientValidationError = $.mage.__('The payment was out of date. Refresh the page to try again')
                                        }
                                        callback(result);
                                    });
                                }
                            }).then(function(checkout) {
                                checkoutInstance = checkout;
                                updateSession();
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
