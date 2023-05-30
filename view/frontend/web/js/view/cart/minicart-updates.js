/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
define(['jquery', 'uiComponent', 'Magento_Customer/js/customer-data', 'extendSdk'], function (
  $,
  Component,
  customerData,
  Extend,
) {
  'use strict'
  const minicartSelector = '[data-block="minicart"]'
  const productItemSelector = '[data-role=product-item]'
  const itemDetailsSelector = 'div.product-item-details'
  const simpleOfferClass = 'extend-minicart-simple-offer'

  const handleUpdate = function () {
    const cartItems = customerData.get('cart')().items

    cartItems.forEach(cartItem => {
      const qtyElem = document.getElementById(`cart-item-${cartItem.item_id}-qty`)
      if (qtyElem) {
        const itemContainerElem = qtyElem.closest(productItemSelector)

        if (itemContainerElem) {
          const simpleOfferElemId = `extend-minicart-simple-offer-${cartItem.item_id}`
          let simpleOfferElem = $(`#${simpleOfferElemId}`, itemContainerElem)

          if (simpleOfferElem.length) {
            // TODO: If warranty already in cart, remove element
          } else {
            // TODO: If warranty already in cart, no need to render

            simpleOfferElem = $('<div>').attr('id', simpleOfferElemId).addClass(simpleOfferClass)
            const itemDetailsElem = $(itemDetailsSelector, itemContainerElem)

            if (itemDetailsElem.length) {
              itemDetailsElem.append(simpleOfferElem)
              Extend.buttons.renderSimpleOffer(`#${simpleOfferElemId}`, {
                referenceId: cartItem.product_sku,
                price: cartItem.product_price_value * 100,
                onAddToCart: function (opts) {
                  addToCart(opts)
                },
              })
            }
          }
        }
      }
    })
  }

  const addToCart = function (opts) {
    // TODO: Handle adding to cart
    console.log('addToCart', opts)
    addToCartSuccess()
  }

  const addToCartSuccess = function () {
    // TODO: Handle successful add to cart
    console.log('addToCartSuccess')
    customerData.reload(['cart'], false)
  }

  return function (config) {
    const extendConfig = {
      storeId: config[0].extendStoreUuid,
      environment: config[0].activeEnvironment,
    }
    Extend.config(extendConfig)

    $(minicartSelector).on('contentUpdated', handleUpdate)
  }
})
