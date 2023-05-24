/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['jquery', 'extendSdk', 'ExtendMagento'], function ($, Extend, ExtendMagento) {
  'use strict'

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })

    const offerDiv = $('#product_protection_offer_' + config[0].productSku)
    // if (offerDiv) {
    //   const secondaryActionsDiv = offerDiv.closest('.actions-secondary')
    //   if (secondaryActionsDiv) {
    //     const primaryActionsDivs = secondaryActionsDiv.siblings('.actions-primary')
    //     if (primaryActionsDivs.length) {
    //       const primaryActionsDiv = primaryActionsDivs[0]
    //       const addToCartButton = primaryActionsDiv.find('button')
    //       if (addToCartButton) {
    //         addToCartButton.html('<span>Modified</span>')
    //       } else {
    //         console.log('addToCartButton not found')
    //       }
    //     } else {
    //       console.log('primaryActionsDivs not found')
    //     }
    //   } else {
    //     console.log('secondaryActionsDiv not found')
    //   }
    // } else {
    //   console.log('offerDiv not found')
    // }

    if (offerDiv) {
      const actionsDiv = offerDiv.closest('.product.actions.product-item-actions')
      if (actionsDiv) {
        const primaryActionsDiv = actionsDiv.find('.actions-primary')
        if (primaryActionsDiv) {
          const addToCartButton = actionsDiv.find('.action.tocart.primary')
          if (addToCartButton) {
            console.log('addToCartButton found!')
          } else {
            console.log('addToCartButton not found')
          }
        } else {
          console.log('primaryActionsDiv not found')
        }
      } else {
        console.log('actionsDiv not found')
      }
    } else {
      console.log('offerDiv not found')
    }

    // $('product_protection_modal_offer_' + config[0].productSku)
    //   .closest('.actions-secondary')
    //   .siblings('.actions-primary')
    //   .find('button')
    //   .html('<span>Modified</span>')

    // Extend.modal.open({
    //   referenceId: config[0].productId,
    //   price: config[0].productPrice * 100,
    //   category: config[0].productCategory,

    //   onClose: function (plan, product) {},
    // })
  }
})
