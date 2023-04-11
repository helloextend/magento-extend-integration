/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals'
    ],
    function (ko,$,Component,quote,totals) {
        "use strict";
        return Component.extend({
            totals: quote.getTotals(),
            shouldRenderSPTotalLineItem : function () {
                if (this.getValue() == 0) {
                    return false;
                } else {
                    return true;
                }
            },
            getShippingProtectionTotal : function () {
                var price = this.getValue();
                return this.getFormattedPrice(price);
            },
            getValue: function() {
                var price = 0;
                if (this.totals() && totals.getSegment('shipping_protection')) {
                    price = totals.getSegment('shipping_protection').value;
                }
                return price;
            }
        });
    }
);