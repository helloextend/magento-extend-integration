/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['extendSdk', 'ExtendMagento'], function (Extend, ExtendMagento) {
  'use strict'

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })

    const addToCartButton = document
      .getElementById('#product_protection_offer_' + config[0].productSku)
      .closest('.product.actions.product-item-actions')
      .querySelector('.action.tocart.primary')

    if (addToCartButton) {
      addToCartButton.addEventListener('click', function () {
        Extend.modal.open({
          referenceId: config[0].productId,
          price: config[0].productPrice * 100,
          category: config[0].productCategory,
          onClose: function (plan, product) {
            console.log('onClose invoked', plan, product)
          },
        })
      })
    } else {
      console.log('addToCartButton not found')
    }
  }
})
