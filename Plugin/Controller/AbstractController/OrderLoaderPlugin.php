<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Controller\AbstractController;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Framework\Registry;

class OrderLoaderPlugin
{
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private Registry $registry;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        Registry $registry
    ){
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->registry = $registry;
    }

    public function afterLoad(\Magento\Sales\Controller\AbstractController\OrderLoader $subject, $result, $request)
    {
        $orderId = (int)$request->getParam('order_id');

        if (!$orderId) {
            return $result;
        }

        $shippingProtectionTotal = $this->shippingProtectionTotalRepository->get($orderId, ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID);

        if (!$shippingProtectionTotal->getData() || sizeof($shippingProtectionTotal->getData()) === 0)
            return $result;

        $order = $this->registry->registry('current_order');

        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes->setShippingProtection(
            [
                'base' => $shippingProtectionTotal->getShippingProtectionBasePrice(),
                'base_currency' => $shippingProtectionTotal->getShippingProtectionBaseCurrency(),
                'price' => $shippingProtectionTotal->getShippingProtectionPrice(),
                'currency' => $shippingProtectionTotal->getShippingProtectionCurrency(),
                'sp_quote_id' => $shippingProtectionTotal->getSpQuoteId()
            ]
        );
        $order->setExtensionAttributes($extensionAttributes);

        return $result;
    }
}