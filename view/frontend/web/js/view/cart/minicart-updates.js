/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
define(['jquery', 'cartUtils', 'extendSdk', 'ExtendMagento'], function (
  $,
  cartUtils,
  Extend,
  ExtendMagento,
) {
  'use strict'
  const minicartSelector = '[data-block="minicart"]'
  const productItemSelector = '[data-role=product-item]'
  const itemDetailsSelector = 'div.product-item-details'
  const simpleOfferClass = 'extend-minicart-simple-offer'

  const handleUpdate = function () {
    const cartItems = cartUtils.getCartItems()

    cartItems.forEach(cartItem => {
      const isWarrantyInCart = ExtendMagento.warrantyInCart({
        lineItemSku: cartItem.product_sku,
        lineItems: cartItems,
      })
      if (cartItem.product_sku === 'extend-protection-plan' || isWarrantyInCart) return
      const qtyElem = document.getElementById(`cart-item-${cartItem.item_id}-qty`)
      if (qtyElem) {
        const itemContainerElem = qtyElem.closest(productItemSelector)
        if (itemContainerElem) {
          const simpleOfferElemId = `extend-minicart-simple-offer-${cartItem.item_id}`
          let simpleOfferElem = itemContainerElem.querySelector(`#${simpleOfferElemId}`)

          if (simpleOfferElem) {
            // TODO: If warranty already in cart, remove element
          } else {
            // TODO: If warranty already in cart, no need to render

            simpleOfferElem = document.createElement('div')
            simpleOfferElem.setAttribute('id', simpleOfferElemId)
            simpleOfferElem.setAttribute('class', simpleOfferClass)
            const itemDetailsElem = itemContainerElem.querySelector(itemDetailsSelector)

            if (itemDetailsElem) {
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

  const getProductQuantity = function (cartItems, product) {
    let quantity = 1

    const matchedCartItem = cartItems.find(cartItem => cartItem.sku === product.id)
    if (matchedCartItem) quantity = matchedCartItem.qty

    return quantity
  }

  const addToCart = function (opts) {
    const { plan, product, quantity } = opts

    if (plan && product) {
      const { planId, price, term, title, coverageType, offerId } = plan
      const { id: productId, price: listPrice } = product

      const planToUpsert = {
        planId,
        price,
        term,
        title,
        coverageType,
      }
      const cartItems = cartUtils.getCartItems().map(cartUtils.mapToExtendCartItem)

      ExtendMagento.upsertProductProtection({
        plan: planToUpsert,
        cartItems,
        productId,
        listPrice,
        offerId,
        quantity: quantity ?? getProductQuantity(cartItems, product),
      }).then(cartUtils.refreshMiniCart)
    }
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
