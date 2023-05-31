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

  const handleAddToCartClick = function (productSku, productPrice, productCategory) {
    Extend.modal.open({
      referenceId: productSku,
      price: productPrice,
      category: productCategory,
      onClose: function (plan, product) {
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
            quantity: 1,
          }).then(refreshCart)
        }
      },
    })
  }

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })

    const productSku = config[0].productSku
    const productPrice = config[0].productPrice * 100
    const productCategory = config[0].productCategory

    const addToCartButton = document
      .querySelector('#product_protection_modal_offer_' + productSku)
      ?.closest('.product.actions.product-item-actions')
      ?.querySelector('.actions-primary')
      ?.querySelector('.action.tocart.primary')

    if (addToCartButton) {
      const handler = function () {
        handleAddToCartClick(productSku, productPrice, productCategory)
      }

      addToCartButton.addEventListener('click', handler)
    }
  }
})
