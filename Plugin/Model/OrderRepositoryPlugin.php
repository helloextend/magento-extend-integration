<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Model\ShippingProtectionFactory;
use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Model\OrderRepository;

class OrderRepositoryPlugin
{
    /**
     * @var OrderExtensionFactory
     */
    private OrderExtensionFactory $orderExtensionFactory;

    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private ShippingProtectionFactory $shippingProtectionFactory;

    /**
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory,
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        ShippingProtectionFactory $shippingProtectionFactory
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
    }

    /**
     * This plugin injects the Shipping Protection record into the order's extension attributes if a matching record is found with a given order id
     *
     * @param OrderRepository $subject
     * @param $result
     * @param $orderId
     * @return mixed
     */
    public function afterGet(\Magento\Sales\Model\OrderRepository $subject, $result, $orderId)
    {
        $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
            $orderId,
            ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID,
            $result
        );

        return $result;
    }

    /**
     * This save the Shipping Protection data from the order's extension attributes into the Shipping Protection totals table, saving the entity type and order ID as well
     *
     * @param OrderRepository $subject
     * @param $result
     * @param $order
     * @return mixed
     */
    public function afterSave(\Magento\Sales\Model\OrderRepository $subject, $result, $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        if ($result && $shippingProtection) {
            $this->shippingProtectionTotalRepository->saveAndResaturateExtensionAttribute(
                $shippingProtection,
                $result,
                ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID
            );
        }
        return $result;
    }

    public function afterGetList(\Magento\Sales\Model\OrderRepository $subject, $result)
    {
        $orders = $result->getItems();
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                    $order->getId(),
                    ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID,
                    $order
                );
            }
        }
        return $result;
    }
}
