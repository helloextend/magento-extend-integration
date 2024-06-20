/*
 * Copyright Extend (c) 2024. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define([], function () {
  'use strict'

  return function (config) {
    window.ExtendConfig = {
      environment: config[0].environment,
      storeId: config[0].storeId,
      currencyCode: config[0].currencyCode,
      isCurrencySupported: config[0].isCurrencySupported,
    }
  }
})
