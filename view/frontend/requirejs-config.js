var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/step-navigator': {
                'Dintero_Checkout/js/model/step-navigator-mixin': true
            },
            'Magento_SalesRule/js/view/payment/discount': {
                'Dintero_Checkout/js/view/payment/discount-mixin': true
            },
            'Magento_Checkout/js/action/set-billing-address': {
                'Dintero_Checkout/js/action/set-billing-address-mixin': true
            },
            'MageWorx_GiftCards/js/view/payment/mageworx-giftcards': {
                'Dintero_Checkout/js/view/payment/mageworx-giftcards-mixin': true
            },
            'MageWorx_GiftCards/js/view/payment/applied-gift-cards-info': {
                'Dintero_Checkout/js/view/payment/applied-giftcards-info-mixin': true
            }
        }
    },
    map: {
        '*': {
            dinteroProductAdd: 'Dintero_Checkout/js/dintero-product-add',
            dinteroLogo: 'Dintero_Checkout/js/view/logo',
            dinteroSdk: 'https://unpkg.com/@dintero/checkout-web-sdk@0.12.9/dist/dintero-checkout-web-sdk.umd.min.js'
        }
    }
};
