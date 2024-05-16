var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/step-navigator': {
                'Dintero_Checkout/js/model/step-navigator-mixin': true
            },
            'Magento_SalesRule/js/view/payment/discount': {
                'Dintero_Checkout/js/view/payment/discount-mixin': true
            }
        }
    },
    map: {
        '*': {
            dinteroProductAdd: 'Dintero_Checkout/js/dintero-product-add',
            dinteroLogo: 'Dintero_Checkout/js/view/logo',
            dinteroSdk: 'https://unpkg.com/@dintero/checkout-web-sdk@0.8.0/dist/dintero-checkout-web-sdk.umd.min.js'
        }
    }
};
