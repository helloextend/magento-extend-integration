/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['ExtendMagento', 'cartUtils'], function (ExtendMagento, cartUtils) {
  'use strict'

  function normalize({ balanceCart }) {
    try {
      const cartItems = cartUtils.getCartItems()
      if (cartItems.length > 0) {
        ExtendMagento.normalizeCart({
          cartItems,
          balanceCart,
          callback: function (err, updates) {
            if (err) {
              return
            }
            if (Object.values(updates).length > 0) {
              window.location.reload()
              cartUtils.refreshMiniCart()
            }
          },
        })
      }
    } catch (error) {
      // Swallow error to avoid impacting customer checkout experience
      /* eslint-disable-next-line no-console */
      console.error(error)
    }
  }

  return function (balanceCart) {
    try {
      // Normalize on cart changes
      cartUtils.getCartData().subscribe(function () {
        normalize(balanceCart)
      })
    } catch (error) {
      // Swallow error to avoid impacting customer checkout experience
      /* eslint-disable-next-line no-console */
      console.error(error)
    }
  }
})
