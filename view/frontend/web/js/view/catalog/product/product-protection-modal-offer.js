/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['extendSdk', 'ExtendMagento'], function (Extend, ExtendMagento) {
  'use strict'

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })

    const productSku = config[0].productSku

    const addToCartButton = document
      .querySelector('#product_protection_modal_offer_' + productSku)
      ?.closest('.product.actions.product-item-actions')
      ?.querySelector('.actions-primary')
      ?.querySelector('.action.tocart.primary')

    if (addToCartButton) {
      addToCartButton.addEventListener('click', function () {
        Extend.modal.open({
          referenceId: productSku,
          price: config[0].productPrice * 100,
          category: config[0].productCategory,
          onClose: function (plan, product) {
            // TODO: [PAR-4187] Add add to cart functionality
            console.log('onClose invoked', { plan }, { product })
          },
        })
      })
    }
  }
})
