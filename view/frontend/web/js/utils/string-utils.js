/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
define([], function () {
  'use strict'

  const sanitizeForElementId = function (str) {
    return str.replace(/[^a-zA-Z0-9-_|]/g, '')
  }

  return {
    sanitizeForElementId,
  }
})
