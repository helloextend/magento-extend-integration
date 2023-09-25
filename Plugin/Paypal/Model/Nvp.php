<?php

    namespace Extend\Integration\Plugin\Paypal\Model;

    use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
    use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
    use Extend\Integration\Service\Extend;
    use Magento\Paypal\Model\Api\AbstractApi;
    use Magento\Quote\Api\Data\CartExtensionFactory;

    /**
     * NVP API wrappers model
     *
     * @method string getToken()
     */
    class Nvp
    {
        /** @var ShippingProtectionTotalRepositoryInterface */
        protected $shippingProtectionTotalRepository;

        /** @var CartExtensionFactory */
        protected $cartExtensionFactory;

        /** @var Extend */
        protected $extend;

        /** @var \Magento\Checkout\Model\Session */
        protected $_checkoutSession;

        public function __construct(
            ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
            CartExtensionFactory                       $cartExtensionFactory,
            Extend                                     $extend,
            \Magento\Checkout\Model\Session            $_checkoutSession
        ) {
            $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
            $this->cartExtensionFactory = $cartExtensionFactory;
            $this->extend = $extend;
            $this->_checkoutSession = $_checkoutSession;
        }

        /**
         * Before Call Method for updated request key
         *
         * @param AbstractApi $subject
         * @param string $title
         * @param array $request
         */
        public function beforeCall(AbstractApi $subject, $title, array $request): array
        {
            if (!$this->extend->isEnabled() || !isset($request['AMT']))
                return [$title, $request];

            $quote = $subject->getData('quote');

            if (!$quote){
                $quote = $this->_checkoutSession->getQuote();
                if (!$quote || !$quote->getId()){
                    return [$title, $request];
                }
            }


            if (!$extensionAttributes = $quote->getExtensionAttributes()) {
                $extensionAttributes = $this->cartExtensionFactory->create();
            }

            if (!$extensionAttributes)
                return [$title, $request];

            $shippingProtection = $extensionAttributes->getShippingProtection();

            if (!$shippingProtection) {
                $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                    $quote->getId(),
                    ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID,
                    $quote
                );
                $extensionAttributes = $quote->getExtensionAttributes();
                $shippingProtection = $extensionAttributes->getShippingProtection();
            }

            if ($shippingProtection && $base = $shippingProtection->getBase()) {
                $request['INSURANCEAMT'] = $base;
                $request['AMT'] =  $request['ITEMAMT'] + $request['SHIPPINGAMT'] + $request['TAXAMT'] + $request['INSURANCEAMT'];
            }

            return [$title, $request];
        }
    }
