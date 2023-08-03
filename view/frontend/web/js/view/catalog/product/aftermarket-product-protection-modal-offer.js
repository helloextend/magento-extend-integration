/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['cartUtils', 'extendSdk', 'ExtendMagento'], function (
  cartUtils,
  Extend,
  ExtendMagento,
) {
  'use strict'

  return function openModal(config) {
    const leadToken = config[0].leadToken
    const storeId = config[0].extendStoreUuid
    Extend.config({ storeId, environment: config[0].activeEnvironment })
    if (leadToken) {
      Extend.aftermarketModal.open({
        leadToken,
        storeId,
        onClose: function (plan, product, quantity) {
          if (plan && product) {
            const { planId, price, term, title, coverageType, offerId } = plan
            const { id: productId, price: listPrice } = product

            const planToUpsert = {
              planId,
              price,
              term,
              title,
              coverageType,
              token: leadToken,
            }
            const cartItems =
              cartUtils.getCartItems()?.map(cartUtils.mapToExtendCartItem) || []

            ExtendMagento.upsertProductProtection({
              plan: planToUpsert,
              cartItems,
              productId,
              listPrice,
              offerId,
              quantity,
            }).then(cartUtils.refreshMiniCart)
          }
        },
      })
    }
  }
})
