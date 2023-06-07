/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['cartUtils', 'extendSdk', 'ExtendMagento'], function (cartUtils, Extend, ExtendMagento) {
  'use strict'

  return function openModal(config) {
    const leadToken = config[0].leadToken
    Extend.aftermarketModal.open({
      leadToken,
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
          }
          const cartItems = cartUtils.getCartItems()?.map(cartUtils.mapToExtendCartItem)

          console.log('upserting: ', {
            plan: planToUpsert,
            cartItems,
            productId,
            listPrice,
            offerId,
            quantity,
          })

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
})
