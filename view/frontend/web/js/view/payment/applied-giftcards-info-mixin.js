define(['jquery', 'mage/utils/wrapper'], function($, wrapper) {
    'use strict'

    return function(target) {
        target.prototype.removeGiftCard = wrapper.wrap(
            target.prototype.removeGiftCard,
            function(originalAction, giftCardCode, event) {
                $(document).one('ajaxComplete', function(event, xhr, settings) {
                    if (settings.url.indexOf('giftcards') !== -1) {
                        var response = xhr.responseJSON;
                        $(document).trigger('mageworx_giftcard_removed_after_async', {
                            response: response,
                            success: response && !response.error
                        });
                    }
                });
                return originalAction(giftCardCode, event);
            }
        );
        return target;
    }
})
