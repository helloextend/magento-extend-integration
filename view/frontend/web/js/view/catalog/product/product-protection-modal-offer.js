/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['jquery', 'extendSdk', 'ExtendMagento'], function ($, Extend, ExtendMagento) {
  'use strict'

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })
    Extend.setDebug(true)

    const productSku = config[0].productSku

    const addToCartButtonForm = $('#product_protection_modal_offer_' + productSkuu)
      .closest('.product.actions.product-item-actions')
      .find('.actions-primary')
      .find('form[data-role="tocart-form"]')

    const addToCartButton = addToCartButtonForm.find('.action.tocart.primary')

    if (addToCartButton) {
      addToCartButton.removeAttr('type').attr('type', 'button')

      addToCartButton.click(function () {
        Extend.modal
          .open({
            referenceId: productSku,
            price: config[0].productPrice * 100,
            category: config[0].productCategory,
            onClose: function (plan, product) {
              console.log('onClose invoked', plan, product)
              addToCartButtonForm.submit()
            },
          })
          .then(function () {
            console.log('modal opened')
          })
          .catch(function (error) {
            console.log('modal error', error)
          })
      })
    } else {
      console.log('addToCartButton not found')
    }
  }
})
