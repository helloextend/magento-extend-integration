<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Quote\Total;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;

class ShippingProtection extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var CartExtensionFactory
     */
    private CartExtensionFactory $cartExtensionFactory;

    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    /**
     * @param SerializerInterface $serializer
     * @param CartExtensionFactory $cartExtensionFactory
     */
    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        SerializerInterface $serializer,
        CartExtensionFactory $cartExtensionFactory
    ) {
        $this->serializer = $serializer;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
    }

    /**
     * Collect Shipping Protection totals
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|ShippingProtection
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $extensionAttributes = $quote->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->cartExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        if ($shippingProtection && $shippingProtection->getPrice() > 0) {
            $total->addTotalAmount($this->getCode(), $shippingProtection->getPrice());
            $total->addBaseTotalAmount(
                $this->getCode(),
                $shippingProtection->getBase() ?: $shippingProtection->getPrice()
            );
        }

        return $this;
    }

    /**
     * Render Shipping Protection Total from value stored in the quote's extension attribute
     *
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $extensionAttributes = $quote->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->cartExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        if (!$shippingProtection || !$shippingProtection->getPrice()) {
            return [];
        }

        return [
            'code' => $this->getCode(),
            'title' => $this->getLabel(),
            'value' => $shippingProtection->getPrice(),
        ];
    }
}
