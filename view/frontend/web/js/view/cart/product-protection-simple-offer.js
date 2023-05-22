/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['jquery', 'extendSdk', 'ExtendMagento'], function ($, Extend, ExtendMagento) {
  'use strict'

  return function (config, element) {
    const activeProductData = {
      referenceId: selectedProduct.selectedSku,
      price: selectedProduct.selectedPrice * 100,
      category: config.productCategory,
    }
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })
    Extend.buttons.renderSimpleOffer(
      '#product_protection_offer_' + config[0].selectedProductSku,
      activeProductData,
    )
  }
})
