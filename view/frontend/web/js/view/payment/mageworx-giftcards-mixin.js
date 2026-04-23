define(['jquery', 'mage/utils/wrapper'], function($, wrapper) {
    'use strict'
    return function(target) {
        target.prototype.activateGiftCard = wrapper.wrap(
            target.prototype.activateGiftCard,
            function(originalAction) {
                $(document).one('ajaxComplete', function(event, xhr, settings) {
                    if (settings.url.indexOf('giftcards') !== -1) {
                        var response = xhr.responseJSON;
                        $(document).trigger('mageworx_giftcard_activated_after_async', {
                            response: response,
                            success: response && !response.error
                        });
                    }
                });
                return originalAction();
            }
        );
        return target;
    }
})
