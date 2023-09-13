<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Service\Extend;
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
    private Extend $extend;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        CartExtensionFactory $cartExtensionFactory,
        ShippingProtectionFactory $shippingProtectionFactory,
        Extend $extend
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
        $this->extend = $extend;
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
        if (!$this->extend->isEnabled())
            return $result;

        $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
            $cartId,
            ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID,
            $result
        );

        return $result;
    }
}
