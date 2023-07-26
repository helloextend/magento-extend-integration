<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Block\Sales\Totals;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Store\Model\Store;

class ShippingProtection extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * @var OrderExtensionFactory
     */
    private OrderExtensionFactory $orderExtensionFactory;
    private InvoiceExtensionFactory $invoiceExtensionFactory;
    private CreditmemoExtensionFactory $creditmemoExtensionFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        OrderExtensionFactory $orderExtensionFactory,
        InvoiceExtensionFactory $invoiceExtensionFactory,
        CreditmemoExtensionFactory $creditmemoExtensionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->creditmemoExtensionFactory = $creditmemoExtensionFactory;
    }

    /**
     * Check if we nedd display full shipping protection total info
     *
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Get the store for this order
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->_order->getStore();
    }

    /**
     * Get the order
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Get the Shipping Protection from the order's extension attributes
     *
     * @return float
     */
    public function getShippingProtection($parent)
    {
        $parentType = $parent->getType();

        if (is_a($parentType, \Magento\Sales\Block\Order\Totals::class, true)) {
            $extensionAttributes = $parent->getOrder()->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->orderExtensionFactory->create();
            }
        } elseif (is_a($parentType, \Magento\Sales\Block\Order\Invoice\Totals::class, true)) {
            $extensionAttributes = $parent->getInvoice()->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->invoiceExtensionFactory->create();
            }
        } elseif (is_a($parentType, \Magento\Sales\Block\Order\Creditmemo\Totals::class, true)) {
            $extensionAttributes = $parent->getCreditmemo()->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->creditmemoExtensionFactory->create();
            }
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        if (!$shippingProtection || !$shippingProtection->getPrice()) {
            return 0;
        }

        return (float) $shippingProtection->getPrice();
    }

    /**
     * Init the totals, including Shipping Protection
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $type = getType($parent->getParentBlock());
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        if ($this->getShippingProtection($parent) > 0) {
            $total = new \Magento\Framework\DataObject([
                'code' => 'shipping_protection',
                'strong' => false,
                'value' => $this->getShippingProtection($parent),
                'label' => __(\Extend\Integration\Service\Extend::SHIPPING_PROTECTION_LABEL),
            ]);

            $parent->addTotal($total, 'shipping');
        }
        return $this;
    }
}
