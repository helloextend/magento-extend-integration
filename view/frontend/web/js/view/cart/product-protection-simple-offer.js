/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['cartUtils', 'extendSdk', 'ExtendMagento'], function (cartUtils, Extend, ExtendMagento) {
  'use strict'

  const getProductQuantity = function (cartItems, product) {
    let quantity = 1

    const matchedCartItem = cartItems.find(cartItem => cartItem.sku === product.id)
    if (matchedCartItem) quantity = matchedCartItem.qty

    return quantity
  }

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
        quantity: quantity ?? getProductQuantity(cartItems, product),
      }).then(cartUtils.refreshMiniCart)
    }
  }

  return function (config) {
    const cartItems = cartUtils.getCartItems()
    const sku = config[0].selectedProductSku
    const isWarrantyInCart = ExtendMagento.warrantyInCart({
      lineItemSku: sku,
      lineItems: cartItems,
    })
    if (sku === 'extend-protection-plan' || isWarrantyInCart) return

    const activeProductData = {
      referenceId: config[0].selectedProductSku,
      price: config[0].selectedProductPrice * 100,
      onAddToCart: handleAddToCartClick,
    }
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })

    Extend.buttons.renderSimpleOffer(
      '#product_protection_offer_' + encodeURIComponent(config[0].selectedProductSku),
      activeProductData,
    )
  }
})
