/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
define(['jquery', 'Magento_Customer/js/customer-data', 'extendSdk', 'ExtendMagento'], function (
  $,
  customerData,
  Extend,
  ExtendMagento,
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

  const getCartItems = function () {
    const cartItems = customerData
      .get('cart')()
      .items?.map(item => {
        return {
          name: item.product_name,
          sku: item.product_sku,
          qty: item.qty,
          price: item.product_price_value * 100,
          item_id: item.product_id,
          options: [],
        }
      })

    return cartItems ?? []
  }

  const refreshCart = function () {
    const sectionsToUpdate = ['cart']
    customerData.invalidate(sectionsToUpdate)
    customerData.reload(sectionsToUpdate, true)
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
      const cartItems = getCartItems()

      ExtendMagento.upsertProductProtection({
        plan: planToUpsert,
        cartItems,
        productId,
        listPrice,
        offerId,
        quantity: quantity ?? getProductQuantity(cartItems, product) ?? 1,
      }).then(refreshCart)
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
