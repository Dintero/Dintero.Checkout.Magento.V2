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
        'Magento_Checkout/js/model/full-screen-loader',
        'Dintero_Checkout/js/action/dintero-payment-service'
    ],
    function ($, Component, placeOrderAction, setPaymentMethodAction, additionalValidators, quote, customerData, ko, fullScreenLoader, paymentService) {
        'use strict';

        let dinteroTemplate = window.checkoutConfig.payment.dintero.isEmbedded ? 'Dintero_Checkout/payment/dintero-embedded' : 'Dintero_Checkout/payment/dintero';
        return Component.extend({
            defaults: {
                template: dinteroTemplate
            },
            redirectAfterPlaceOrder: false,
            isVisible: ko.observable(true),
            showButton: ko.observable(true),
            initElement: function() {
                var _this = this;
                this._super();
                paymentService.init();
                $(paymentService).on('dintero.payment.failed', function(event, message) {
                    _this.errorHandler(event, message)
                })
            },
            errorHandler: function(event, errorMessage) {
                this.messageContainer.addErrorMessage({message: errorMessage});
            },
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
                        var msg;
                        if (response.url) {
                            $.mage.redirect(response.url);
                        } else {
                            fullScreenLoader.stopLoader(true);

                            msg = response['error'];
                            if (typeof msg === 'object') {
                                msg = msg.join('\n');
                            }

                            if (msg) {
                                alert(msg);
                            }
                        }
                    }
                });
            }
        });
    }
);
