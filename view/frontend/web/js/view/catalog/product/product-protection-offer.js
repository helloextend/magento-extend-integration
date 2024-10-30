/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define([
  'jquery',
  'cartUtils',
  'extendSdk',
  'ExtendMagento',
  'stringUtils',
  'currencyUtils',
], function ($, cartUtils, Extend, ExtendMagento, stringUtils, currencyUtils) {
  'use strict'

  const getProductQuantity = function () {
    let quantity = 1

    const quantityInput = document.querySelector('.input-text.qty')

    if (quantityInput) quantity = parseInt(quantityInput.value)

    return quantity
  }

  // Get the chosen simple product based on the configurable options selected.
  function getActiveProductConfig() {
    const swatches = $('div.swatch-attribute', '.product-info-main')
    let selectedProductSku = null
    let selectedProductPrice = null

    if (swatches.length > 0) {
      const swatchesElem = $('[data-role=swatch-options]', '.product-info-main')
      const swatchRenderer = swatchesElem.data('mageSwatchRenderer')
        ? swatchesElem.data('mageSwatchRenderer')
        : swatchesElem.data('mage-SwatchRenderer')

      if (swatchRenderer) {
        const selectedProducts = swatchRenderer._CalcProducts()
        const selectedId =
          // eslint-disable-next-line no-undef -- Magento injected global
          _.isArray(selectedProducts) && selectedProducts.length === 1
            ? selectedProducts[0]
            : null
        if (selectedId && selectedId !== '') {
          selectedProductPrice =
            swatchRenderer.options.jsonConfig.optionPrices[selectedId]
              .finalPrice.amount
          selectedProductSku = swatchRenderer.options.jsonConfig.sku[selectedId]
        }
      }
    } else {
      const spConfig = $('#product_addtocart_form').data('mageConfigurable')
        ?.options?.spConfig
      const selectedId = $(
        'input[name=selected_configurable_option]',
        '.product-info-main',
      ).val()
      if (selectedId && selectedId !== '') {
        selectedProductSku =
          spConfig && spConfig.skus ? spConfig.skus[selectedId] : null
      }
    }
    return { selectedProductSku, selectedProductPrice }
  }

  return function (config) {
    Extend.config({
      storeId: config[0].extendStoreUuid,
      environment: config[0].activeEnvironment,
      currency: config[0].currencyCode,
    })

    for (let key in config) {
      const cents = currencyUtils.Money.fromAmount(
        config[key].selectedProductPrice,
        config[key].currencyCode,
      ).cents

      Extend.buttons.render(
        '#product_protection_offer_' +
          stringUtils.sanitizeForElementId(config[key].selectedProductSku),
        {
          referenceId: config[key].selectedProductSku,
          price: cents,
          category: config[key].productCategory,
        },
      )
    }

    // Listening for product options being chosen on configurable products.  Display offer once all required options are chosen.
    $('div.product-options-wrapper', '.product-info-main').on(
      'change',
      function () {
        const selectedProduct = getActiveProductConfig()
        const buttonInstance = Extend.buttons.instance(
          '#product_protection_offer_' +
            stringUtils.sanitizeForElementId(config[0].selectedProductSku),
        )

        const cents = currencyUtils.Money.fromAmount(
          selectedProduct.selectedProductPrice,
          config[0].currencyCode,
        ).cents

        const activeProductData = {
          referenceId: selectedProduct.selectedProductSku,
          price: cents,
          category: config[0].productCategory,
        }
        if (buttonInstance) {
          buttonInstance.setActiveProduct(activeProductData)
        } else {
          Extend.buttons.render(
            '#product_protection_offer_' +
              stringUtils.sanitizeForElementId(config[0].selectedProductSku),
            activeProductData,
          )
        }
      },
    )

    // Listen for the add to cart button to be clicked.  Show modal offer on qualifying simple and configurable products if no offer was chosen by the customer.
    document
      .getElementById('product-addtocart-button')
      .addEventListener('click', function (event) {
        const buttonInstance = Extend.buttons.instance(
          '#product_protection_offer_' +
            stringUtils.sanitizeForElementId(config[0].selectedProductSku),
        )

        if (buttonInstance) {
          if (config.length === 1) {
            let selectedProduct

            if (
              buttonInstance.getActiveProduct().id ===
              config[0].selectedProductSku
            ) {
              selectedProduct = config[0]
            } else {
              selectedProduct = getActiveProductConfig()
            }

            const cartItems = cartUtils
              .getCartItems()
              .map(cartUtils.mapToExtendCartItem)
            const quantity = getProductQuantity()

            const selectedPlan = buttonInstance.getPlanSelection()

            // If a plan is selected, add it to the cart
            if (selectedPlan) {
              const { planId, price, term, title, coverageType, offerId } =
                selectedPlan
              const {
                selectedProductSku: productId,
                selectedProductPrice: listPrice,
              } = selectedProduct

              const planToUpsert = {
                planId,
                price,
                term,
                title,
                coverageType,
              }

              ExtendMagento.upsertProductProtection({
                plan: planToUpsert,
                cartItems,
                productId,
                listPrice,
                offerId,
                quantity,
              }).then(cartUtils.refreshMiniCart)
              // If no plan is selected, open the offer modal
            } else {
              event.preventDefault()
              event.stopPropagation()

              const cents = currencyUtils.Money.fromAmount(
                selectedProduct.selectedProductPrice,
                config[0].currencyCode,
              ).cents

              Extend.modal.open({
                referenceId: selectedProduct.selectedProductSku,
                price: cents,
                category: config[0].productCategory,
                onClose: function (plan, product) {
                  if (plan && product) {
                    const {
                      planId,
                      price,
                      term,
                      title,
                      coverageType,
                      offerId,
                    } = plan
                    const { id: productId, price: listPrice } = product

                    const planToUpsert = {
                      planId,
                      price,
                      term,
                      title,
                      coverageType,
                    }

                    ExtendMagento.upsertProductProtection({
                      plan: planToUpsert,
                      cartItems,
                      productId,
                      listPrice,
                      offerId,
                      quantity,
                    }).then(cartUtils.refreshMiniCart)
                  }
                  $('#product-addtocart-button').trigger('submit')
                },
              })
            }
          }
        }
      })
  }
})
