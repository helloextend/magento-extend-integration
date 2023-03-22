<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\QuoteRepository;
use Extend\Integration\Model\ShippingProtectionFactory;

class QuoteRepositoryPlugin
{
    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    /**
     * @var CartExtensionFactory
     */
    private CartExtensionFactory $cartExtensionFactory;
    private ShippingProtectionFactory $shippingProtectionFactory;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        CartExtensionFactory $cartExtensionFactory,
        ShippingProtectionFactory $shippingProtectionFactory
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
    }

    /**
     * This plugin injects the Shipping Protection record into the quote's extension attributes if a matching record is found with a given quote id
     *
     * @param QuoteRepository $subject
     * @param $result
     * @param $cartId
     * @return mixed
     */
    public function afterGet(\Magento\Quote\Model\QuoteRepository $subject, $result, $cartId)
    {
        $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
            $cartId,
            ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID,
            $result
        );

        return $result;
    }

    /**
     * This save the Shipping Protection data from the quote's extension attributes into the Shipping Protection totals table, saving the entity type and quote ID as well
     *
     * @param QuoteRepository $subject
     * @param $result
     * @param $quote
     * @return mixed
     */
    public function afterSave(\Magento\Quote\Model\QuoteRepository $subject, $result, $quote)
    {
        $extensionAttributes = $quote->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->cartExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        if ($result && $shippingProtection) {
            $this->shippingProtectionTotalRepository->saveAndResaturateExtensionAttribute(
                $shippingProtection,
                $result,
                ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID
            );
        }
        return $result;
    }
}
