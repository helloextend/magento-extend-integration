<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Controller\AbstractController;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Model\ShippingProtectionFactory;
use Magento\Framework\Registry;

class OrderLoaderPlugin
{
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private Registry $registry;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        Registry $registry,
        ShippingProtectionFactory $shippingProtectionFactory
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->registry = $registry;
    }

    public function afterLoad(
        \Magento\Sales\Controller\AbstractController\OrderLoader $subject,
        $result,
        $request
    ) {
        $orderId = (int) $request->getParam('order_id');

        if (!$orderId) {
            return $result;
        }

        $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
            $orderId,
            ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID,
            $this->registry->registry('current_order')
        );

        return $result;
    }
}
