/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(
  [
      'uiComponent',
      'ko',
      'Magento_Customer/js/customer-data',
      'Magento_Checkout/js/model/quote',
      'extendSdk',
      'ExtendMagento',
  ],
  function (Component, ko, customerData, magentoQuote, Extend, ExtendMagento) {
      "use strict";
      return Component.extend({
        defaults: {
            template: 'Extend_Integration/checkout/summary/shipping-protection-offer'
        },
        shouldRenderSP: function() {
            if (window.ExtendConfig.environment && window.ExtendConfig.storeId) return true
            return false
        },
        renderSP: function() {
            const items = ExtendMagento.formatCartItemsForSp(customerData.get('cart')().items)
            const totals = magentoQuote.getTotals()
            
            Extend.shippingProtection.render(
                {
                    selector: '#extend-shipping-protection', 
                    items,
                    isShippingProtectionInCart: ExtendMagento.isShippingProtectionInOrder(totals()),
                    onEnable: function(quote){
                        ExtendMagento.addSpPlanToOrder({
                          quote, 
                          totals: totals(),
                          callback: function(err, resp){
                            if (err) {
                              return;
                            }
                            // Reload is not necessary at the offers current location. SP Totals will show on the next checkout step.
                            // If the offer is moved anywhere the SP price is showing (Order Summary), a reload is necessary
                            // window.location.reload();
                          }               
                        })
                    },
                    onDisable: function(){
                        ExtendMagento.removeSpPlanFromOrder({
                            callback: function(err, resp){
                              if (err) {
                                return;
                              }
                            }
                        })
                    },
                    onUpdate: function(quote){
                        ExtendMagento.updateSpPlanInOrder({
                            quote, 
                            totals: totals(),
                            callback: function(err, resp){
                              if (err) {
                                return;
                              }
                              // Reload is not necessary at the offers current location. SP Totals will show on the next checkout step.
                              // If the offer is moved anywhere the SP price is showing (Order Summary), a reload is necessary
                              // window.location.reload();
                            }               
                        })
                    }
                }
            )
        },
        initialize: function () {
            this._super();
            Extend.config({
                storeId: window.ExtendConfig.storeId, 
                environment: window.ExtendConfig.environment,
            })

            // Update SP on cart changes
            customerData.get('cart').subscribe(function(cart) {
                const items = ExtendMagento.formatCartItemsForSp(cart.items)
            
                Extend.shippingProtection.update({ items })
            })
        }
    });
  }
);