define([
    'uiComponent',
    'Dintero_Checkout/js/action/dintero-payment-service',
], function(Component, paymentService) {
    return Component.extend({
        defaults: {
            template: 'Dintero_Checkout/payment/popout'
        },
        initialize: function() {
            this._super();
            paymentService.init('#checkoutSteps');
        }
    });
});
