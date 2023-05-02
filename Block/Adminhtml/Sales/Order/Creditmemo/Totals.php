<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use function Extend\Integration\Block\Adminhtml\Sales\__;

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
     * return 0 if not found
     *
     * @return float
     */
    public function getShippingProtection(): float
    {
        $source = $this->getParentBlock()->getSource();

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
                    default:
                        return 0;
                }
            }
            $shippingProtection = $extensionAttributes->getShippingProtection();
            if ($shippingProtection && $shippingProtection->getPrice()) {
                return (float) $shippingProtection->getPrice();
            } else {
                return 0;
            }
        }
    }

    public function getOriginalShippingProtection()
    {
        $source = $this->getParentBlock()->getSource();
        if ($source->getData('original_shipping_protection')) {
            return $source->getData('original_shipping_protection');
        }
    }

    /**
     * Initialize Shipping Protection total
     *
     * @return $this
     */
    public function initTotals()
    {
        if ($this->getShippingProtection() !== null) {

            $total = new \Magento\Framework\DataObject(['code' => 'shipping_protection_block', 'block_name' => $this->getNameInLayout()]);

            $this->getParentBlock()->addTotal($total, 'shipping');
        }
        return $this;
    }
}
