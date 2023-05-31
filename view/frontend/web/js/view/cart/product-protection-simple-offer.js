/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['Magento_Customer/js/customer-data', 'extendSdk', 'ExtendMagento'], function (
  customerData,
  Extend,
  ExtendMagento,
) {
  'use strict'

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

  const handleAddToCartClick = function (opts) {
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
        quantity: quantity ?? 1,
      }).then(refreshCart)
    }
  }

  return function (config, element) {
    const activeProductData = {
      referenceId: config[0].selectedProductSku,
      price: config[0].selectedProductPrice * 100,
      category: config[0].productCategory,
      onAddToCart: handleAddToCartClick,
    }
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })
    Extend.buttons.renderSimpleOffer(
      '#product_protection_offer_' + config[0].selectedProductSku,
      activeProductData,
    )
  }
})
