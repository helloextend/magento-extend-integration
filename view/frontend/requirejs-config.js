var config = {
  paths: {
    extendSdk: 'https://sdk.helloextend.com/extend-sdk-client/v1/extend-sdk-client.min',
    ExtendMagento:
      'https://sdk.helloextend.com/extend-sdk-client-magento-addon/v1/extend-sdk-client-magento-addon.min',
  },
  map: {
    '*': {
      productProtectionOffer: 'Extend_Integration/js/view/catalog/product/product-protection-offer',
      simpleProductProtectionOffer:
        'Extend_Integration/js/view/cart/product-protection-simple-offer',
      productProtectionModalOffer:
        'Extend_Integration/js/view/catalog/product/product-protection-modal-offer',
    },
  },
}
