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

  const handleAddToCartClick = function (
    productSku,
    productPrice,
    productCategory,
  ) {
    Extend.modal.open({
      referenceId: productSku,
      price: productPrice,
      category: productCategory,
      onClose: function (plan, product) {
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
            quantity: 1,
          }).then(cartUtils.refreshMiniCart)
        }
      },
    })
  }

  return function (config) {
    Extend.config({
      storeId: config[0].extendStoreUuid,
      environment: config[0].activeEnvironment,
      currency: config[0].currencyCode,
    })

    const cents = currencyUtils.Money.fromAmount(
      config[0].productPrice,
      config[0].currencyCode,
    ).cents

    const productSku = config[0].productSku
    const productCategory = config[0].productCategory

    const addToCartButton = document
      .querySelector(
        '#product_protection_modal_offer_' +
          stringUtils.sanitizeForElementId(productSku),
      )
      ?.closest('.product.actions.product-item-actions')
      ?.querySelector('.actions-primary')
      ?.querySelector('.action.tocart.primary')

    if (addToCartButton) {
      const handler = function () {
        handleAddToCartClick(productSku, cents, productCategory)
      }

      addToCartButton.addEventListener('click', handler)
    }
  }
})
