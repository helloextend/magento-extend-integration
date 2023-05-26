/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

define(['extendSdk'], function (Extend) {
  'use strict'

  return function (config) {
    const extendConfig = {
      storeId: config[0].extendStoreUuid,
      environment: config[0].activeEnvironment,
    }
    // eslint-disable-next-line no-console
    console.log('Setting Extend config', extendConfig)
    Extend.config(extendConfig)
  }
})
