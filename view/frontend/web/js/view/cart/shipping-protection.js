define([
    'Magento_Checkout/js/model/totals',
    'ExtendMagento',
], function (totals, ExtendMagento) {
    'use strict';

    return function () {
        try {
            if (totals.getSegment('shipping_protection')) {
                ExtendMagento.removeSpPlanFromOrder({
                    callback: function (_err, _resp) {
                        // Add custom callback here if needed for integration
                    },
                });
            }
        } catch (error) {
            // Swallow error to avoid impacting customer checkout experience
            console.error(error)
        }
    };
});
