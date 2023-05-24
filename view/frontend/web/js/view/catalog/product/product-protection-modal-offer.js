/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['jquery', 'extendSdk', 'ExtendMagento'], function ($, Extend, ExtendMagento) {
  'use strict'

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })

    const productSku = config[0].productSku

    const addToCartForm = $('#product_protection_modal_offer_' + productSku)
      ?.closest('.product.actions.product-item-actions')
      ?.find('.actions-primary')
      ?.find('form[data-role="tocart-form"]')

    if (addToCartForm) {
      const addToCartButton = addToCartForm.find('.action.tocart.primary')

      if (addToCartButton) {
        addToCartButton.removeAttr('type').attr('type', 'button')

        addToCartButton.click(function () {
          Extend.modal.open({
            referenceId: productSku,
            price: config[0].productPrice * 100,
            category: config[0].productCategory,
            onClose: function (plan, product) {
              console.log('onClose invoked', plan, product)
              addToCartForm.submit()
            },
          })
        })
      }
    }
  }
})
