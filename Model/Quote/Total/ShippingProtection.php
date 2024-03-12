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
use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Tax\Model\Calculation;

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
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Calculation
     */
    private Calculation $calculation;

    /**
     * @param SerializerInterface $serializer
     * @param CartExtensionFactory $cartExtensionFactory
     */
    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        CartExtensionFactory $cartExtensionFactory,
        Calculation $calculation
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->calculation = $calculation;
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

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $extensionAttributes = $quote->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->cartExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        // Check if merchant has enabled taxability on shipping protection product
        $spTaxClassId = $this->getSpTaxClassId();
        $customerTaxClassId = $quote->getCustomerTaxClassId();
        $store = $this->storeManager->getStore();
        $request = $this->calculation->getRateRequest(
            $quote->getShippingAddress(),
            $quote->getBillingAddress(),
            $customerTaxClassId,
            $store
        );
        $taxRate = $this->calculation->getRate($request->setProductClassId($spTaxClassId));
        
        if ($shippingProtection && $shippingProtection->getPrice() > 0) {
            // If $spTaxClassId is set we need to calculate the order tax including the shipping protection cost
            if ($spTaxClassId && $spTaxClassId != 0) {
                $spTaxAmount = ($taxRate/100) * $shippingProtection->getBase();
                $shippingProtection->setShippingProtectionTax($spTaxAmount);

                $result = $this->shippingProtectionTotalRepository->get($quote->getId(), ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID);
                $this->shippingProtectionTotalRepository->save(
                    $result->getEntityId(),
                    $result->getEntityTypeId(),
                    $shippingProtection->getSpQuoteId(),
                    $shippingProtection->getPrice(),
                    $shippingProtection->getCurrency(),
                    $shippingProtection->getBase(),
                    $shippingProtection->getBaseCurrency(),
                    $spTaxAmount,
                );
                $total->addTotalAmount('tax', $spTaxAmount);
                $total->addBaseTotalAmount('tax', $spTaxAmount);
                $total->addTotalAmount($this->getCode(), $shippingProtection->getPrice());
                $total->addBaseTotalAmount(
                    $this->getCode(),
                    $shippingProtection->getBase() ?: $shippingProtection->getPrice()
                );
            } else {
                $total->addTotalAmount($this->getCode(), $shippingProtection->getPrice());
                $total->addBaseTotalAmount(
                    $this->getCode(),
                    $shippingProtection->getBase() ?: $shippingProtection->getPrice()
                );
            }
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

    private function getSpTaxClassId()
    {
        $spTaxClassId = $this->scopeConfig->getValue('extend_plans/shipping_protection/shipping_protection_tax_class', ScopeInterface::SCOPE_STORE);
        return $spTaxClassId;
    }
}
