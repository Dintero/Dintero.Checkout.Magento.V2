define(['jquery', 'mage/utils/wrapper'], function($, wrapper) {
    'use strict'

    let mixin = {
        cancel: function() {
            if (this.validate()) {
                $(document).trigger('coupon_cancel_before');
                this._super();
            }
        }
    }

    return function(target) {
        return target.extend(mixin);
    }
})
