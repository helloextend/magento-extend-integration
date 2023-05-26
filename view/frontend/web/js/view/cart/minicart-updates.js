define(['jquery', 'uiComponent', 'Magento_Customer/js/customer-data', 'extendSdk'], function (
  $,
  Component,
  customerData,
  Extend,
) {
  'use strict'

  // return function (config, element) {
  //   console.log(config, element)
  // }

  return Component.extend({
    initialize: function () {
      this._super()
      $('[data-block="minicart"]').on('contentUpdated', this.handleUpdate)
    },

    handleUpdate: function () {
      const cartItems = customerData.get('cart')().items
      cartItems.forEach(cartItem => {
        console.log(cartItem)
        const qtyElem = document.getElementById(`cart-item-${cartItem.item_id}-qty`)
        if (qtyElem) {
          const productItemElem = qtyElem.closest('[data-role=product-item]')

          if (productItemElem) {
            var blockID = 'warranty-offers-' + cartItem.item_id
            var warrantyElem = $('#' + blockID, productItemElem)
            let productSku = cartItem.product_sku

            if (!warrantyElem.length) {
              warrantyElem = $('<div>').attr('id', blockID).addClass('product-item-warranty')

              const productItemDetailsElem = $('div.product-item-details', productItemElem)

              if (productItemDetailsElem.length) {
                productItemDetailsElem.append(warrantyElem)
                Extend.buttons.renderSimpleOffer(`#${blockID}`, {
                  referenceId: productSku,
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
