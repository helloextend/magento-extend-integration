/**
 * Collection of currency utility methods that are useful to both adminhtml and frontend.
 * These methods will be made available via require.js and can be required by any JS file.
 */

define(['extendSdk'], function (Extend) {
  'use strict'

  /**
   * Rounding method designed for currency
   * credit: http://www.jacklmoore.com/notes/rounding-in-javascript/
   * needed because "toFixed()" rounds inconsistently for even/odd numbers
   */
  function round(value, decimals = 2) {
    const numeric = typeof value === 'string' ? parseFloat(value) : value

    return Number(
      Math.round(Number(numeric + 'e' + decimals)) + 'e-' + decimals,
    )
  }

  /**
   * Money class for handling currency values
   */
  class Money {
    /**
     * Map of registered currencies
     */
    static currencies = {}

    /**
     * Value in cents
     */
    cents

    /**
     * Currency object
     */
    currency

    /**
     * Create a Money object from an amount and currency code
     */
    static fromAmount(amount, providedCurrencyCode) {
      let currencyCode = providedCurrencyCode

      // Fallback to the currency code from the Extend config if no currency code is provided.
      // This is useful for cases where the currency code is not provided to the frontend JS.
      if ((!currencyCode || currencyCode === 'undefined') && Extend.config()) {
        currencyCode = Extend.config().currency
      }

      const currency = Money.currencies[currencyCode]
      if (!currency) {
        throw new Error('Invalid currency ' + currencyCode)
      }
      const numeric = typeof amount === 'string' ? parseFloat(amount) : amount

      // Since we're dealing with whole numbers when converting to cents, we can use round() with 0 decimals
      return new Money(round(numeric * currency.subunitToUnit, 0), currencyCode)
    }

    /**
     * Register a currency definition
     */
    static register(currency) {
      this.currencies[currency.isoCode] = currency
    }

    /**
     * Create a Money object from a number of cents and a currency code
     */
    constructor(cents, currencyCode) {
      this.cents = cents
      const currency = Money.currencies[currencyCode]
      if (!currency) {
        throw new Error('Invalid currency ' + currencyCode)
      }
      this.currency = currency
    }

    /**
     * Get the ISO code of the currency
     */
    get isoCode() {
      return this.currency.isoCode
    }

    /**
     * Format the money value as a string
     */
    format(symbol = true, locale = 'en-US') {
      return new Intl.NumberFormat(locale, {
        style: symbol ? 'currency' : 'decimal',
        currency: symbol ? this.currency.isoCode : undefined,
        minimumFractionDigits: 2,
      }).format(this.cents / this.currency.subunitToUnit)
    }
  }

  Money.register({
    isoCode: 'USD',
    subunitToUnit: 100,
    decimalMark: '.',
    symbol: '$',
    thousandsSeparator: ',',
  })

  Money.register({
    isoCode: 'CAD',
    subunitToUnit: 100,
    decimalMark: '.',
    symbol: '$',
    thousandsSeparator: ',',
  })

  return {
    round,
    Money,
  }
})
