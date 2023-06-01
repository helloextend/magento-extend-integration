var config = {
  paths: {
    extendSdk: 'https://sdk.helloextend.com/extend-sdk-client/v1/extend-sdk-client.min',
    ExtendMagento:
      'https://sdk.helloextend.com/extend-sdk-client-magento-addon/v1/extend-sdk-client-magento-addon.min',
  },
  map: {
    '*': {
      productProtectionOffer: 'Extend_Integration/js/view/catalog/product/product-protection-offer',
      productProtectionModalOffer:
        'Extend_Integration/js/view/catalog/product/product-protection-modal-offer',
      simpleProductProtectionOffer:
        'Extend_Integration/js/view/cart/product-protection-simple-offer',
      minicartSimpleOffer: 'Extend_Integration/js/view/cart/minicart-updates',
      cartUtils: 'Extend_Integration/js/utils/cart-utils',
    },
  },
}
