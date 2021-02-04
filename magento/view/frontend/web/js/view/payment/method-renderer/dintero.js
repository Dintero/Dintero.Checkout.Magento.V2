define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Dintero_Checkout/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, placeOrderAction, setPaymentMethodAction, additionalValidators, quote, customerData, ko, fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Dintero_Checkout/payment/dintero'
            },
            redirectAfterPlaceOrder: false,
            isVisible: ko.observable(true),
            showButton: ko.observable(true),
            getLogoUrl: function() {
                return window.checkoutConfig.payment.dintero.logoUrl;
            },
            continueToDintero: function () {
                if (additionalValidators.validate()) {
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(this.placeOrder);
                    return false;
                }
            },

            placeOrder: function () {
                customerData.invalidate(['cart']);
                $.ajax({
                    url: window.checkoutConfig.payment.dintero.placeOrderUrl,
                    type: 'post',
                    context: this,
                    dataType: 'json',
                    beforeSend: function () {
                        fullScreenLoader.startLoader();
                    },
                    success: function (response) {
                        var preparedData,
                            msg,

                            /**
                             * {Function}
                             */
                            alertActionHandler = function () {
                                // default action
                            };

                        if (response.url) {
                            $.mage.redirect(response.url);
                        } else {
                            fullScreenLoader.stopLoader(true);

                            msg = response['error'];
                            if (typeof msg === 'object') {
                                msg = msg.join('\n');
                            }

                            if (msg) {
                                alert(
                                    {
                                        content: msg,
                                        actions: {

                                            /**
                                             * {Function}
                                             */
                                            always: alertActionHandler
                                        }
                                    }
                                );
                            }
                        }
                    }
                });
            }
        });
    }
);