define([
    'uiComponent',
    'jquery',
    'uiRegistry',
    'Dintero_Checkout/js/action/dintero-payment-service'
], function(Component, $, registry, paymentService) {
    return Component.extend({
        defaults: {
            template: 'Dintero_Checkout/payment/popout'
        },
        initialize: function() {
            var _this = this;
            this._super();
            paymentService.init('#checkoutSteps');
            $(paymentService).on('dintero.payment.failed', function(event, message) {
                _this.errorHandler(event, message);
                setTimeout(() => window.location.reload(), 10000);
            })
        },

        errorHandler: function(event, errorMessage) {
            registry.get('checkout.errors').messageContainer.addErrorMessage({message: errorMessage});
        }
    });
});
