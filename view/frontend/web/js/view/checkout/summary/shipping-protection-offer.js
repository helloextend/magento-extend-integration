/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define([
  'uiComponent',
  'ko',
  'Magento_Customer/js/customer-data',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/action/get-totals',
  'extendSdk',
  'ExtendMagento',
], function (Component, ko, customerData, magentoQuote, getTotalsAction, Extend, ExtendMagento) {
  'use strict'
  return Component.extend({
    defaults: {
      template: 'Extend_Integration/checkout/summary/shipping-protection-offer',
    },
    shouldRenderSP: function () {
      if (
        window.ExtendConfig &&
        window.ExtendConfig.environment &&
        window.ExtendConfig.storeId &&
        window.checkoutConfig.extendEnable === '1'
      )
        return true
      return false
    },
    renderSP: function () {
      try {
        const items = ExtendMagento.formatCartItemsForSp(customerData.get('cart')().items)
        const totals = magentoQuote.getTotals()

        Extend.shippingProtection.render({
          selector: '#extend-shipping-protection',
          items,
          isShippingProtectionInCart: ExtendMagento.isShippingProtectionInOrder(totals()),
          onEnable: function (quote) {
            ExtendMagento.addSpPlanToOrder({
              quote,
              totals: totals(),
              callback: function (err, _resp) {
                if (err) {
                  return
                }

                // getTotalsAction updates the `total_segments` returned in totals(). If this is not run then if you
                // make another action that triggers one of these functions the totals() output will be stale which can
                // lead to undesirable effects such as SP not staying checked if you uncheck and recheck it
                getTotalsAction([])

                // Reload is not necessary at the offers current location. SP Totals will show on the next checkout step.
                // If the offer is moved anywhere the SP price is showing (Order Summary), a reload is necessary
                // window.location.reload();
              },
            })
          },
          onDisable: function () {
            ExtendMagento.removeSpPlanFromOrder({
              callback: function (err, _resp) {
                if (err) {
                  return
                }

                // getTotalsAction updates the `total_segments` returned in totals(). If this is not run then if you
                // make another action that triggers one of these functions the totals() output will be stale which can
                // lead to undesirable effects such as SP not staying checked if you uncheck and recheck it
                getTotalsAction([])
              },
            })
          },
          onUpdate: function (quote) {
            ExtendMagento.updateSpPlanInOrder({
              quote,
              totals: totals(),
              callback: function (err, _resp) {
                if (err) {
                  return
                }

                // getTotalsAction updates the `total_segments` returned in totals(). If this is not run then if you
                // make another action that triggers one of these functions the totals() output will be stale which can
                // lead to undesirable effects such as SP not staying checked if you uncheck and recheck it
                getTotalsAction([])

                // Reload is not necessary at the offers current location. SP Totals will show on the next checkout step.
                // If the offer is moved anywhere the SP price is showing (Order Summary), a reload is necessary
                // window.location.reload();
              },
            })
          },
        })
      } catch (error) {
        // Swallow error to avoid impacting customer checkout experience
        /* eslint-disable-next-line no-console */
        console.error(error)
      }
    },
    initialize: function () {
      this._super()

      try {
        Extend.config({
          storeId: window.ExtendConfig.storeId,
          environment: window.ExtendConfig.environment,
        })

        // Update SP on cart changes
        customerData.get('cart').subscribe(function (cart) {
          const items = ExtendMagento.formatCartItemsForSp(cart.items)

          Extend.shippingProtection.update({ items })
        })
      } catch (error) {
        // Swallow error to avoid impacting customer checkout experience
        /* eslint-disable-next-line no-console */
        console.error(error)
      }
    },
  })
})
