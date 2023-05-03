/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define([
    'jquery',
    'extendSdk',
    'ExtendMagento',
], function ($, Extend, ExtendMagento) {
        'use strict';

        return function(config, element) {

            var swatches = $('div.swatch-attribute', this.mainWrap);
            var selectedSku = null;

            if (swatches.length > 0 ) {
                var swatchesElem = this.options.isInProductView ?
                    $('[data-role=swatch-options]', this.mainWrap) :
                    $('[data-role^=swatch-option-]', this.mainWrap);
                var swatchRenderer = swatchesElem.data('mageSwatchRenderer');

                if (swatchRenderer) {
                    var selectedProducts = swatchRenderer._CalcProducts();
                    var selectedId = _.isArray(selectedProducts) && selectedProducts.length === 1 ? selectedProducts[0] : null;
                    if (selectedId && selectedId !== '') {
                        selectedSku = swatchRenderer.options.jsonConfig.skus[selectedId];
                    }
                }
            } else if (this.options.isInProductView) {
                var selectedId = $('input[name=selected_configurable_option]', this.mainWrap).val();
                if (selectedId && selectedId !== '') {
                    var spConfig = this.addToCartForm.data('mageConfigurable').options.spConfig;
                    selectedSku = spConfig && spConfig.skus ? spConfig.skus[selectedId] : null;
                }
            }

            return selectedSku ? selectedSku : this.options.productSku;

            Extend.config({ storeId: config.extendStoreUuid });
            Extend.buttons.render('#product_protection_offer', {
                referenceId: config.selectedProductSku,
                price: config.selectedProductPrice,
                category: config.productCategory
            });

        };

    }
)