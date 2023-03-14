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

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        CartExtensionFactory $cartExtensionFactory
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->cartExtensionFactory = $cartExtensionFactory;
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
        $shippingProtectionTotal = $this->shippingProtectionTotalRepository->get($cartId, ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID);

        if (!$shippingProtectionTotal->getData() || sizeof($shippingProtectionTotal->getData()) === 0)
            return $result;

        $extensionAttributes = $result->getExtensionAttributes();
        $extensionAttributes->setShippingProtection(
            [
                'base' => $shippingProtectionTotal->getShippingProtectionBasePrice(),
                'base_currency' => $shippingProtectionTotal->getShippingProtectionBaseCurrency(),
                'price' => $shippingProtectionTotal->getShippingProtectionPrice(),
                'currency' => $shippingProtectionTotal->getShippingProtectionCurrency(),
                'sp_quote_id' => $shippingProtectionTotal->getSpQuoteId()
            ]
        );
        $result->setExtensionAttributes($extensionAttributes);

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

        if (
            isset($shippingProtection['base']) &&
            isset($shippingProtection['base_currency']) &&
            isset($shippingProtection['price']) &&
            isset($shippingProtection['currency']) &&
            isset($shippingProtection['sp_quote_id'])
        ) {
            $this->shippingProtectionTotalRepository->save($quote->getEntityId(), ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID, $shippingProtection['sp_quote_id'], $shippingProtection['price'], $shippingProtection['currency'], $shippingProtection['base'], $shippingProtection['base_currency']);
        }

        return $result;
    }
}
