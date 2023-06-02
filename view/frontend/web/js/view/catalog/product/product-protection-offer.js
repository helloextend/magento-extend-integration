/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['jquery', 'extendSdk', 'ExtendMagento'], function ($, Extend, ExtendMagento) {
  'use strict'

  // Get the chosen simple product based on the configurable options selected.
  function getActiveProductConfig() {
    const swatches = $('div.swatch-attribute', '.product-info-main')
    let selectedProductSku = null
    let selectedPrice = null

    if (swatches.length > 0) {
      const swatchesElem = $('[data-role=swatch-options]', '.product-info-main')
      const swatchRenderer = swatchesElem.data('mageSwatchRenderer')

      if (swatchRenderer) {
        const selectedProducts = swatchRenderer._CalcProducts()
        const selectedId =
          _.isArray(selectedProducts) && selectedProducts.length === 1 ? selectedProducts[0] : null
        if (selectedId && selectedId !== '') {
          selectedPrice =
            swatchRenderer.options.jsonConfig.optionPrices[selectedId].finalPrice.amount
          selectedProductSku = swatchRenderer.options.jsonConfig.skus[selectedId]
        }
      }
    } else {
      const spConfig = $('#product_addtocart_form').data('mageConfigurable').options.spConfig
      const selectedId = $('input[name=selected_configurable_option]', '.product-info-main').val()
      if (selectedId && selectedId !== '') {
        selectedProductSku = spConfig && spConfig.skus ? spConfig.skus[selectedId] : null
      }
    }
    return { selectedProductSku, selectedPrice }
  }

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })
    for (let key in config) {
      Extend.buttons.render('#product_protection_offer_' + config[key].selectedProductSku, {
        referenceId: config[key].selectedProductSku,
        price: config[key].selectedProductPrice * 100,
        category: config[key].productCategory,
      })
    }
    // Listening for product options being chosen on configurable products.  Display offer once all required options are chosen.
    $('div.product-options-wrapper', '.product-info-main').on('change', function () {
      const selectedProduct = getActiveProductConfig()
      const buttonInstance = Extend.buttons.instance(
        '#product_protection_offer_' + config[0].selectedProductSku,
      )
      const activeProductData = {
        referenceId: selectedProduct.selectedProductSku,
        price: selectedProduct.selectedPrice * 100,
        category: config.productCategory,
      }
      if (buttonInstance) {
        buttonInstance.setActiveProduct(activeProductData)
      } else {
        Extend.buttons.render(
          '#product_protection_offer_' + config[0].selectedProductSku,
          activeProductData,
        )
      }
    })
    // Listen for the add to cart button to be clicked.  Show modal offer on qualifying simple and configurable products if no offer was chosen by the customer.
    document.getElementById('product-addtocart-button').addEventListener('click', function () {
      let selectedProduct
      const buttonInstance = Extend.buttons.instance(
        '#product_protection_offer_' + config[0].selectedProductSku,
      )
      if (buttonInstance) {
        const plan = buttonInstance.getPlanSelection()
        if (!plan && config.length === 1) {
          if (buttonInstance.getActiveProduct().id === config[0].selectedProductSku) {
            selectedProduct = config[0]
          } else {
            selectedProduct = getActiveProductConfig()
          }
          Extend.modal.open({
            referenceId: selectedProduct.selectedProductSku,
            price: selectedProduct.selectedPrice * 100,
            category: config.productCategory,
            onClose: function (plan, product) {
              // TODO: [PAR-4187] Add add to cart functionality
              console.log('onClose invoked', { plan }, { product })
            },
          })
        }
      }
    })
  }
})
