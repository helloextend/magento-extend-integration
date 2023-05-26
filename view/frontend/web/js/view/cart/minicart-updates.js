define(['jquery', 'uiComponent', 'Magento_Customer/js/customer-data', 'extendSdk'], function (
  $,
  Component,
  customerData,
  Extend,
) {
  'use strict'

  return Component.extend({
    initialize: function () {
      this._super()
      $('[data-block="minicart"]').on('contentUpdated', this.handleUpdate)
    },

    handleUpdate: function () {
      const cartItems = customerData.get('cart')().items

      cartItems.forEach(cartItem => {
        const qtyElem = document.getElementById(`cart-item-${cartItem.item_id}-qty`)
        if (qtyElem) {
          const itemContainerElem = qtyElem.closest('[data-role=product-item]')

          if (itemContainerElem) {
            const simpleOfferElemId = 'extend-minicart-simple-offer-' + cartItem.item_id
            let simpleOfferElem = $('#' + simpleOfferElemId, itemContainerElem)

            if (simpleOfferElem.length) {
              // TODO: If warranty already in cart, remove element
            } else {
              // TODO: If warranty already in cart, no need to render

              simpleOfferElem = $('<div>')
                .attr('id', simpleOfferElemId)
                .addClass('extend-minicart-simple-offer')
              const itemDetailsElem = $('div.product-item-details', itemContainerElem)

              if (itemDetailsElem.length) {
                itemDetailsElem.append(simpleOfferElem)
                Extend.buttons.renderSimpleOffer(`#${simpleOfferElemId}`, {
                  referenceId: cartItem.product_sku,
                  price: cartItem.product_price_value * 100,
                  // category: cartItem.category,
                  onAddToCart: function (opts) {
                    console.log('onAddToCart invoked', opts)
                  },
                })
              }
            }
          }
        }
      })
    },
  })
})
