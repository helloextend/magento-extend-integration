/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
define([
  'jquery',
  'cartUtils',
  'extendSdk',
  'ExtendMagento',
  'currencyUtils',
], function ($, cartUtils, Extend, ExtendMagento, currencyUtils) {
  'use strict'
  const minicartSelector = '[data-block="minicart"]'
  const productItemSelector = '[data-role=product-item]'
  const itemDetailsSelector = 'div.product-item-details'
  const simpleOfferClass = 'extend-minicart-simple-offer'

  const handleUpdate = function () {
    const cartItems = cartUtils.getCartItems()
    let categories

    cartItems.forEach(async cartItem => {
      const isWarrantyInCart = ExtendMagento.warrantyInCart({
        lineItemSku: cartItem.product_sku,
        lineItems: cartItems,
      })
      if (
        cartItem.product_sku === 'extend-protection-plan' ||
        cartItem.product_sku === 'xtd-pp-pln' ||
        isWarrantyInCart
      ) {
        return
      }
      const qtyElem = document.getElementById(
        `cart-item-${cartItem.item_id}-qty`,
      )
      if (qtyElem) {
        const itemContainerElem = qtyElem.closest(productItemSelector)
        if (itemContainerElem) {
          const simpleOfferElemId =
            'extend-minicart-simple-offer-' + cartItem.item_id

          let simpleOfferElem = itemContainerElem.querySelector(
            `#${simpleOfferElemId}`,
          )

          if (simpleOfferElem) {
            // TODO: If warranty already in cart, remove element
          } else {
            // TODO: If warranty already in cart, no need to render

            // Only fetch categories if we actually get to the point of needing to render an offer
            // Once this is fetched once though we should never need to fetch categories again
            // for the current execution of handleUpdate.
            // Why Ajax? There's no JavaScript API to get categories in Magento and we can't use a
            // ViewModel because the minicart doesn't rerender in cases such as items being added to cart.
            if (!categories) {
              categories = await new Promise((resolve, _reject) => {
                $.ajax({
                  url:
                    window.BASE_URL + 'extend_integration/minicart/categories',
                  type: 'GET',
                  dataType: 'json',
                  success: function (response) {
                    resolve(response)
                  },
                  error: function (xhr, status, error) {
                    console.error(error)
                    resolve({})
                  },
                })
              })
            }

            simpleOfferElem = document.createElement('div')
            simpleOfferElem.setAttribute('id', simpleOfferElemId)
            simpleOfferElem.setAttribute('class', simpleOfferClass)
            const itemDetailsElem =
              itemContainerElem.querySelector(itemDetailsSelector)

            if (itemDetailsElem) {
              const currencyCode = Extend.config().currency

              const cents = currencyUtils.Money.fromAmount(
                cartItem.product_price_value,
                currencyCode,
              ).cents

              itemDetailsElem.append(simpleOfferElem)
              Extend.buttons.renderSimpleOffer(`#${simpleOfferElemId}`, {
                referenceId: cartItem.product_sku,
                price: cents,
                category: categories[cartItem.item_id],
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

    const matchedCartItem = cartItems.find(
      cartItem => cartItem.sku === product.id,
    )
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
      const cartItems = cartUtils
        .getCartItems()
        .map(cartUtils.mapToExtendCartItem)

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

  /**
   * We are limited on event types to listen for updates in the minicart and use `contentUpdated`.
   * We debounce the handleUpdate function to avoid making multiple requests in quick succession as the minicart is updated,
   * which will throw an error on the sdk when attempting to renderSimpleOffer with the same elemId.
   * https://helloextend.atlassian.net/browse/MINT-3100
   */
  const debounce = (func, wait) => {
    let timeout
    return function (...args) {
      clearTimeout(timeout)
      timeout = setTimeout(() => func.apply(this, args), wait)
    }
  }

  const debouncedHandleUpdate = debounce(handleUpdate, 1000) // Adjust the wait time as needed

  return function (config) {
    const extendConfig = {
      storeId: config[0].extendStoreUuid,
      environment: config[0].activeEnvironment,
      currency: config[0].currencyCode,
    }
    Extend.config(extendConfig)

    $(minicartSelector).on('contentUpdated', debouncedHandleUpdate)
  }
})
