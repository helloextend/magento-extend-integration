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
], function (
  Component,
  ko,
  customerData,
  magentoQuote,
  getTotalsAction,
  Extend,
  ExtendMagento,
) {
  'use strict'

  return Component.extend({
    defaults: {
      template: 'Extend_Integration/checkout/summary/shipping-protection-offer',
    },
    shouldRenderSP: function () {
      const shippingMethod = magentoQuote.shippingMethod()
      if (
        window.ExtendConfig &&
        window.ExtendConfig.environment &&
        window.ExtendConfig.storeId &&
        window.checkoutConfig.extendEnable === '1' &&
        window.ExtendConfig.isCurrencySupported &&
        (!shippingMethod ||
          (shippingMethod && shippingMethod.method_code !== 'pickup'))
      )
        return true
      return false
    },
    renderSP: function () {
      try {
        const items = ExtendMagento.formatQuoteItemsForSp(
          magentoQuote.getItems(),
        )
        const totals = magentoQuote.getTotals()

        Extend.shippingProtection.render({
          selector: '#extend-shipping-protection',
          items,
          isShippingProtectionInCart: ExtendMagento.isShippingProtectionInOrder(
            totals(),
          ),
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
          currency: window.ExtendConfig.currencyCode,
        })

        magentoQuote.shippingMethod.subscribe(function (shippingMethod) {
          if (!shippingMethod) {
            return
          }

          if (shippingMethod.method_code === 'pickup') {
            // If we don't destroy the instance then the next time we reload the _instance property will
            // short-circuit the render and no offer will appear. This happens if you select in-store pickup
            // after the offer initially renders and then change back to a shipping option.
            Extend.shippingProtection.destroy()

            const totals = magentoQuote.getTotals()
            if (ExtendMagento.isShippingProtectionInOrder(totals())) {
              ExtendMagento.removeSpPlanFromOrder({
                callback: function (err, _resp) {
                  if (err) {
                    return
                  }

                  // getTotalsAction updates the `total_segments` returned in totals().
                  getTotalsAction([])
                },
              })
            }
          }
        })

        // Update SP on cart changes
        // Even though we're using quoteItems now, which have discount information, the best event to subscribe
        // to still appears to be the cart.
        customerData.get('cart').subscribe(function () {
          const items = ExtendMagento.formatQuoteItemsForSp(
            magentoQuote.getItems(),
          )
          const totals = magentoQuote.getTotals()
          const isShippingProtectionInCart =
            ExtendMagento.isShippingProtectionInOrder(totals())

          Extend.shippingProtection.update({
            items,
            isShippingProtectionInCart,
          })
        })
      } catch (error) {
        // Swallow error to avoid impacting customer checkout experience
        /* eslint-disable-next-line no-console */
        console.error(error)
      }
    },
  })
})
