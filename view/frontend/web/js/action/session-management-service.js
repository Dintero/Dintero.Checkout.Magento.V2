define([
    'Magento_Checkout/js/model/url-builder',
    'mage/storage'
], function(urlBuilder, storage) {

    /**
     * Make request
     *
     * @param endpoint
     * @param data
     * @returns {*}
     */
    const makeRequest = function(endpoint, data) {

        if (Object.keys(data || {}).length) {
            data = JSON.stringify(data);
        }

        return storage.post(
            urlBuilder.createUrl(endpoint, {}),
            data || {},
            true,
            'application/json'
        )
    }

    return function() {

        this.initSession = function(cartId) {
            return makeRequest('/dintero/checkout/session-init', {cartId: cartId});
        }

        this.updateSession = function() {
            return makeRequest('/dintero/checkout/session-update');
        }

        this.updateTotals = function(sessionId) {
            return makeRequest('/dintero/checkout/update-totals', {sessionId: sessionId});
        }

        this.validateSession = function(sessionId) {
            return makeRequest('/dintero/checkout/session-validate', {sessionId: sessionId});
        }
    }
});
