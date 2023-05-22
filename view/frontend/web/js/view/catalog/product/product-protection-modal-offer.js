/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['jquery', 'extendSdk', 'ExtendMagento'], function ($, Extend, ExtendMagento) {
  'use strict'

  return function (config, element) {
    Extend.config({ storeId: config[0].extendStoreUuid, environment: config[0].activeEnvironment })
    Extend.modal.open({
      referenceId: config[0].productId,
      price: config[0].productPrice * 100,
      category: config[0].productCategory,

      onClose: function (plan, product) {},
    })
  }
})
