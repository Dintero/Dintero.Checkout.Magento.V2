var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/step-navigator': {
                'Dintero_Checkout/js/model/step-navigator-mixin': true
            }
        }
    },
    map: {
        '*': {
            dinteroProductAdd: 'Dintero_Checkout/js/dintero-product-add',
            dinteroLogo: 'Dintero_Checkout/js/view/logo',
            dinteroSdk: 'https://unpkg.com/@dintero/checkout-web-sdk@0.0.15/dist/checkout-web-sdk.umd.js'
        }
    }
};
