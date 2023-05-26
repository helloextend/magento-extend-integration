define(['jquery', 'uiComponent', 'Magento_Customer/js/customer-data', 'extendSdk'], function (
  $,
  Component,
  customerData,
  Extend,
) {
  'use strict'

  return Component.extend({
    minicartSelector: '[data-block="minicart"]',
    productItemSelector: '[data-role=product-item]',
    itemDetailsSelector: 'div.product-item-details',
    simpleOfferClass: 'extend-minicart-simple-offer',

    initialize: function () {
      this._super()
      $(this.minicartSelector).on('contentUpdated', this.handleUpdate.bind(this))
    },

    handleUpdate: function () {
      const self = this
      const cartItems = customerData.get('cart')().items

      cartItems.forEach(cartItem => {
        const qtyElem = document.getElementById(`cart-item-${cartItem.item_id}-qty`)
        if (qtyElem) {
          const itemContainerElem = qtyElem.closest(this.productItemSelector)

          if (itemContainerElem) {
            const simpleOfferElemId = `extend-minicart-simple-offer-${cartItem.item_id}`
            let simpleOfferElem = $(`#${simpleOfferElemId}`, itemContainerElem)

            if (simpleOfferElem.length) {
              // TODO: If warranty already in cart, remove element
            } else {
              // TODO: If warranty already in cart, no need to render

              simpleOfferElem = $('<div>')
                .attr('id', simpleOfferElemId)
                .addClass(this.simpleOfferClass)
              const itemDetailsElem = $(this.itemDetailsSelector, itemContainerElem)

              if (itemDetailsElem.length) {
                itemDetailsElem.append(simpleOfferElem)
                Extend.buttons.renderSimpleOffer(`#${simpleOfferElemId}`, {
                  referenceId: cartItem.product_sku,
                  price: cartItem.product_price_value * 100,
                  onAddToCart: function (opts) {
                    self.addToCart(opts)
                  },
                })
              }
            }
          }
        }
      })
    },

    addToCart: function (opts) {
      // TODO: Handle adding to cart
      console.log('addToCart', opts)
      this.addToCartSuccess()
    },

    addToCartSuccess: function () {
      customerData.reload(['cart'], false)
    },
  })
})
