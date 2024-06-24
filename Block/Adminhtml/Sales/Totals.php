<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Block\Adminhtml\Sales;

use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * @var OrderExtensionFactory
     */
    private OrderExtensionFactory $orderExtensionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var InvoiceExtensionFactory
     */
    private InvoiceExtensionFactory $invoiceExtension;

    /**
     * @var CreditmemoExtensionFactory
     */
    private CreditmemoExtensionFactory $creditmemoExtension;

    /**
     * Shipping Protection totals admin block constructor.
     *
     * @param Context $context
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param InvoiceExtensionFactory $invoiceExtension
     * @param CreditmemoExtensionFactory $creditmemoExtension
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        OrderExtensionFactory $orderExtensionFactory,
        InvoiceExtensionFactory $invoiceExtension,
        CreditmemoExtensionFactory $creditmemoExtension,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->orderRepository = $orderRepository;
        $this->invoiceExtension = $invoiceExtension;
        $this->creditmemoExtension = $creditmemoExtension;
    }

    /**
     * Get Shipping Protection total from the entity's extension_attributes,
     * returns shipping protection price, null if not found
     *
     * @return float|null
     */
    public function getShippingProtection(): float|null
    {
        $source = $this->getParentBlock()->getSource();

        if ($source->getOmitSp()) {
          return NULL;
        }

        $shippingProtection = $source->getShippingProtection();

        if ($shippingProtection !== null) {
            return (float)$shippingProtection;
        } else {
            $extensionAttributes = $source->getExtensionAttributes();
            if ($extensionAttributes === null) {
                switch ($source->getEntityType()) {
                    case 'order':
                        $extensionAttributes = $this->orderExtensionFactory->create();
                        break;
                    case 'invoice':
                        $extensionAttributes = $this->invoiceExtensionFactory->create();
                        break;
                    case 'creditmemo':
                        $extensionAttributes = $this->creditmemoExtensionFactory->create();
                        break;
                }
            }
            $shippingProtection = $extensionAttributes->getShippingProtection();
            if ($shippingProtection && ($shippingProtection->getPrice() > 0 || $shippingProtection->getOfferType() === ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE)) {
                return (float) $shippingProtection->getPrice();
            } else {
                return NULL;
            }
        }
    }

    /**
     * Initialize Shipping Protection total
     *
     * @return $this
     */
    public function initTotals()
    {
        $shippingProtectionPrice = $this->getShippingProtection();
        if ($shippingProtectionPrice === NULL) {
            return $this;
        }

        if ($shippingProtectionPrice >= 0) {
            $total = new \Magento\Framework\DataObject(
                [
                    'code' => 'shipping_protection',
                    'value' => $this->getShippingProtection(),
                    'label' => __(\Extend\Integration\Service\Extend::SHIPPING_PROTECTION_LABEL),
                ]
            );
            $this->getParentBlock()->addTotal($total, 'shipping');
        }
        return $this;
    }
}
