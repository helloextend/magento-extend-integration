/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['jquery', 'extendSdk', 'ExtendMagento'], function ($, Extend, ExtendMagento) {
  'use strict'

  function getActiveProductConfig() {
    const swatches = $('div.swatch-attribute', '.product-info-main')
    let selectedSku = null
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
          selectedSku = swatchRenderer.options.jsonConfig.skus[selectedId]
        }
      }
    } else if (this.options.isInProductView) {
      const spConfig = $('#product_addtocart_form').data('mageConfigurable').options.spConfig
      const selectedId = $('input[name=selected_configurable_option]', '.product-info-main').val()
      if (selectedId && selectedId !== '') {
        selectedSku = spConfig && spConfig.skus ? spConfig.skus[selectedId] : null
      }
    }
    return { selectedSku, selectedPrice }
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

    $('div.product-options-wrapper', '.product-info-main').on('change', function () {
      const selectedProduct = getActiveProductConfig()
      const buttonInstance = Extend.buttons.instance('#product_protection_offer')
      const activeProductData = {
        referenceId: selectedProduct.selectedSku,
        price: selectedProduct.selectedPrice * 100,
        category: config.productCategory,
      }
      if (buttonInstance) {
        buttonInstance.setActiveProduct(activeProductData)
      } else {
        Extend.buttons.render('#product_protection_offer', activeProductData)
      }
    })
  }
})
