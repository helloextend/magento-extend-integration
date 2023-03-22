<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Block\Sales\Totals;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderExtensionFactory;
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

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        OrderExtensionFactory $orderExtensionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderExtensionFactory = $orderExtensionFactory;
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
    public function getShippingProtection()
    {
        $extensionAttributes = $this->_order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        if (!$shippingProtection || !$shippingProtection->getPrice()) {
            return 0;
        }

        return (float)$shippingProtection->getPrice();
    }

    /**
     * Init the totals, including Shipping Protection
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();

        if ($this->getShippingProtection() > 0) {
            $total = new \Magento\Framework\DataObject(
                [
                    'code' => 'shipping_protection',
                    'strong' => false,
                    'value' => $this->getShippingProtection(),
                    'label' => __(\Extend\Integration\Service\Extend::SHIPPING_PROTECTION_LABEL),
                ]
            );

            $parent->addTotal($total, 'shipping');
        }
        return $this;
    }
}
