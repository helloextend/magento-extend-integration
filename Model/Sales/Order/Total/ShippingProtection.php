<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Sales\Order\Total;

use Magento\Sales\Model\Order;

class ShippingProtection extends \Magento\Sales\Model\Order\Total\AbstractTotal
{

    /**
     * Collect Shipping Protection value from the extension attributes and populate the shipping_protection total property on the order
     *
     * @param Order $order
     * @return $this
     */
    public function collect(Order $order): ShippingProtection
    {

        if ($shippingProtection = $order->getOrder()->getExtensionAttributes()->getShippingProtection()) {
            $shippingProtectionBasePrice = $shippingProtection->getBase();
            $shippingProtectionPrice = $shippingProtection->getPrice();

            $order->setBaseShippingProtection($shippingProtectionBasePrice);
            $order->setShippingProtection($shippingProtectionPrice);

            $order->setGrandTotal($order->getGrandTotal() + $order->getShippingProtection());
            $order->setBaseGrandTotal($order->getBaseGrandTotal() + $order->getBaseShippingProtection());
        }

        return $this;
    }
}
