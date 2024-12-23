/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define([
  'jquery',
  'Magento_Checkout/js/view/summary/abstract-total',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/totals',
], function ($, Component, quote, totals) {
  'use strict'
  return Component.extend({
    defaults: {
      template: 'Extend_Integration/checkout/summary/shipping-protection',
    },
    totals: quote.getTotals(),
    isDisplayed: function () {
      return this.getValue() !== null
    },
    getShippingProtectionTotal: function () {
      var price = this.getValue()
      return this.getFormattedPrice(price)
    },
    getValue: function () {
      var price = null
      try {
        if (this.totals() && totals.getSegment('shipping_protection')) {
          price = totals.getSegment('shipping_protection').value
        }
      } catch (error) {
        // Swallow error to avoid impacting customer checkout experience
        console.error(error)
      }
      return price
    },
  })
})
