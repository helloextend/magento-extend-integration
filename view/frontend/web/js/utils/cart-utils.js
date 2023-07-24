/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
define(['Magento_Customer/js/customer-data'], function (customerData) {
  'use strict'

  const getCartItems = function () {
    return customerData.get('cart')().items ?? []
  }

  const getCartData = function () {
    return customerData.get('cart')
  }
  const refreshMiniCart = function () {
    const sectionsToUpdate = ['cart']
    customerData.invalidate(sectionsToUpdate)
    customerData.reload(sectionsToUpdate, true)
  }

  const mapToExtendCartItem = function (magentoCartItem) {
    return {
      name: magentoCartItem.product_name,
      sku: magentoCartItem.product_sku,
      qty: magentoCartItem.qty,
      price: magentoCartItem.product_price_value * 100,
      item_id: magentoCartItem.product_id,
      options: [],
    }
  }

  return {
    getCartItems,
    refreshMiniCart,
    mapToExtendCartItem,
    getCartData,
  }
})
