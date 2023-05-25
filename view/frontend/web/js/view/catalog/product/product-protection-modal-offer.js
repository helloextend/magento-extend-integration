/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['jquery', 'extendSdk', 'ExtendMagento'], function ($, Extend, ExtendMagento) {
  'use strict'

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })

    const productSku = config[0].productSku

    const addToCartForm = document
      .querySelector('#product_protection_modal_offer_' + productSku)
      ?.closest('.product.actions.product-item-actions')
      ?.querySelector('.actions-primary')
      ?.querySelector('form[data-role="tocart-form"]')

    if (addToCartForm) {
      const addToCartButton = addToCartForm.closest('.action.tocart.primary')

      if (addToCartButton) {
        addToCartButton.addEventListener('click', function (event) {
          // this button is of type submit so clicking it automatically submits the form, the form now gets submitted on modal close
          event.preventDefault()

          Extend.modal.open({
            referenceId: productSku,
            price: config[0].productPrice * 100,
            category: config[0].productCategory,
            onClose: function (plan, product) {
              // TODO: [PAR-4187] Add add to cart functionality
              console.log('onClose invoked', { plan }, { product })
              addToCartForm.submit()
            },
          })
        })
      }
    }
  }
})
