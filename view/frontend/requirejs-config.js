// eslint-disable-next-line no-unused-vars, @typescript-eslint/no-unused-vars -- Globally available by Magento via requirejs
var config = {
  paths: {
    extendSdk:
      'https://sdk.helloextend.com/extend-sdk-client/v1/extend-sdk-client.min',
    ExtendMagento:
      'https://sdk.helloextend.com/extend-sdk-client-magento-addon/v1/extend-sdk-client-magento-addon.min',
  },
  map: {
    '*': {
      productProtectionOffer:
        'Extend_Integration/js/view/catalog/product/product-protection-offer',
      productProtectionModalOffer:
        'Extend_Integration/js/view/catalog/product/product-protection-modal-offer',
      aftermarketProductProtectionModalOffer:
        'Extend_Integration/js/view/catalog/product/aftermarket-product-protection-modal-offer',
      simpleProductProtectionOffer:
        'Extend_Integration/js/view/cart/product-protection-simple-offer',
      minicartSimpleOffer: 'Extend_Integration/js/view/cart/minicart-updates',
      cartUtils: 'Extend_Integration/js/utils/cart-utils',
      stringUtils: 'Extend_Integration/js/utils/string-utils',
      spQuoteConfig: 'Extend_Integration/js/view/checkout/sp-quote-config',
      checkoutCartTotalsSp:
        'Extend_Integration/js/view/cart/shipping-protection',
    },
  },
}
