/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(
    [
        'jquery',
        'extendSdk',
        'ExtendMagento'
    ],
    function ($, Extend, ExtendMagento) {
        'use strict';

        var options = {};

        return function(config, element) {
            Extend.config({ storeId: config.extendStoreUuid });
            Extend.buttons.render('#product_protection_offer', {
                referenceId: config.selectedProductSku,
                price: config.selectedProductPrice,
                category: config.productCategory
            });
        };

    }
)