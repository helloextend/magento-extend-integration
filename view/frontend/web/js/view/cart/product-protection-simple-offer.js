/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['cartUtils', 'extendSdk', 'ExtendMagento'], function (cartUtils, Extend, ExtendMagento) {
  'use strict'

  const handleAddToCartClick = function (opts) {
    const { plan, product, quantity } = opts

    if (plan && product) {
      const { planId, price, term, title, coverageType, offerId } = plan
      const { id: productId, price: listPrice } = product

      const planToUpsert = {
        planId,
        price,
        term,
        title,
        coverageType,
      }
      const cartItems = cartUtils.getCartItems().map(cartUtils.mapToExtendCartItem)

      ExtendMagento.upsertProductProtection({
        plan: planToUpsert,
        cartItems,
        productId,
        listPrice,
        offerId,
        quantity: quantity ?? 1,
      }).then(cartUtils.refreshMiniCart)
    }
  }

  return function (config) {
    const activeProductData = {
      referenceId: config[0].selectedProductSku,
      price: config[0].selectedProductPrice * 100,
      category: config[0].productCategory,
      onAddToCart: handleAddToCartClick,
    }
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })
    Extend.buttons.renderSimpleOffer(
      '#product_protection_offer_' + config[0].selectedProductSku,
      activeProductData,
    )
  }
})
