/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['Magento_Customer/js/customer-data', 'ExtendMagento', 'cartUtils'], function (
  customerData,
  ExtendMagento,
  cartUtils,
) {
  'use strict'

  function normalize() {
    try {
      const cartItems = cartUtils.getCartItems()
      if (cartItems.length > 0) {
        ExtendMagento.normalizeCart({
          cartItems,
          callback: function (err, updates) {
            if (err) {
              return
            }
            if (Object.values(updates).length > 0) {
              window.location.reload()
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

  return function () {
    try {
      // Normalize on cart changes
      customerData.get('cart').subscribe(function () {
        normalize()
      })
    } catch (error) {
      // Swallow error to avoid impacting customer checkout experience
      /* eslint-disable-next-line no-console */
      console.error(error)
    }
  }
})
