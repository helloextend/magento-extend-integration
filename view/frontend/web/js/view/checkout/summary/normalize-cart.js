/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define([
  'uiComponent',
  'ko',
  'Magento_Customer/js/customer-data',
  'Magento_Checkout/js/action/get-totals',
  'ExtendMagento',
], function (Component, ko, customerData, getTotalsAction, ExtendMagento) {
  'use strict'

  function normalize() {
    try {
      const cartItems = customerData.get('cart')().items
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
  return Component.extend({
    defaults: {
      template: 'Extend_Integration/checkout/summary/normalize-cart',
    },
    shouldNormalize: function () {
      if (window.checkoutConfig.extendEnable === '1') {
        return true
      }
    },
    normalizeCart: normalize,
    initialize: function () {
      this._super()

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
    },
  })
})
