/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define([
  'cartUtils',
  'extendSdk',
  'ExtendMagento',
  'stringUtils',
  'currencyUtils',
], function (cartUtils, Extend, ExtendMagento, stringUtils, currencyUtils) {
  'use strict'

  const getProductQuantity = function (cartItems, product) {
    let quantity = 1

    const matchedCartItem = cartItems.find(
      cartItem => cartItem.sku === product.id,
    )
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
      const cartItems = cartUtils
        .getCartItems()
        .map(cartUtils.mapToExtendCartItem)

      ExtendMagento.upsertProductProtection({
        plan: planToUpsert,
        cartItems,
        productId,
        listPrice,
        offerId,
        quantity: quantity ?? getProductQuantity(cartItems, product),
      }).then(function () {
        // The underlying code in the refreshMiniCart function forces a cart
        // invalidation that effects more than just the mini cart. We rely on this
        // to happen before refreshing so that we never get a stale state where the
        // refreshed page cart is missing the virtual product and thus tries to show offers.
        cartUtils.refreshMiniCart()
        window.location.reload()
      })
    }
  }

  const renderSimpleOffer = function (cartItems, config) {
    const [data] = config
    if (!data) return

    const sku = data.selectedProductSku
    const isWarrantyInCart = ExtendMagento.warrantyInCart({
      lineItemSku: sku,
      lineItems: cartItems,
    })
    if (
      sku === 'extend-protection-plan' ||
      sku === 'xtd-pp-pln' ||
      isWarrantyInCart
    ) {
      return
    }

    const cents = currencyUtils.Money.fromAmount(
      data.selectedProductPrice,
      data.currencyCode,
    ).cents

    const activeProductData = {
      referenceId: data.selectedProductSku,
      price: cents,
      category: data.productCategory,
      onAddToCart: handleAddToCartClick,
    }
    Extend.config({
      storeId: data.extendStoreUuid,
      environment: data.activeEnvironment,
      currency: data.currencyCode,
    })

    Extend.buttons.renderSimpleOffer(
      '#product_protection_offer_' +
        stringUtils.sanitizeForElementId(data.selectedProductSku),
      activeProductData,
    )
  }

  return function (config) {
    const cartData = cartUtils.getCartData()
    const cartItems = cartUtils.getCartItems()
    if (cartItems.length > 0) {
      renderSimpleOffer(cartItems, config)
    }
    cartData.subscribe(function (updatedCartData) {
      const cartItems = updatedCartData.items
      renderSimpleOffer(cartItems, config)
    })
  }
})
