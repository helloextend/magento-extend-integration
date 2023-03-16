<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

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

    /**
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory,
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
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
        $shippingProtectionTotal = $this->shippingProtectionTotalRepository->get($orderId, ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID);

        if (!$shippingProtectionTotal->getData() || sizeof($shippingProtectionTotal->getData()) === 0)
            return $result;

        $extensionAttributes = $result->getExtensionAttributes();
        $extensionAttributes->setShippingProtection(
            [
                'base' => $shippingProtectionTotal->getShippingProtectionBasePrice(),
                'base_currency' => $shippingProtectionTotal->getShippingProtectionBaseCurrency(),
                'price' => $shippingProtectionTotal->getShippingProtectionPrice(),
                'currency' => $shippingProtectionTotal->getShippingProtectionCurrency(),
                'sp_quote_id' => $shippingProtectionTotal->getSpQuoteId()
            ]
        );
        $extensionAttributes->setExtendShippingProtectionBase($shippingProtectionTotal->getShippingProtectionBasePrice());
        $extensionAttributes->setExtendShippingProtectionBaseCurrency($shippingProtectionTotal->getShippingProtectionBaseCurrency());
        $extensionAttributes->setExtendShippingProtectionPrice($shippingProtectionTotal->getShippingProtectionPrice());
        $extensionAttributes->setExtendShippingProtectionCurrency($shippingProtectionTotal->getShippingProtectionCurrency());
        $extensionAttributes->setExtendShippingProtectionQuoteId($shippingProtectionTotal->getSpQuoteId());
        $result->setExtensionAttributes($extensionAttributes);

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
        if (
            isset($shippingProtection['base']) &&
            isset($shippingProtection['base_currency']) &&
            isset($shippingProtection['price']) &&
            isset($shippingProtection['currency']) &&
            isset($shippingProtection['sp_quote_id'])
        ) {
            $this->shippingProtectionTotalRepository->save($result->getEntityId(), ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID, $shippingProtection['sp_quote_id'], $shippingProtection['price'], $shippingProtection['currency'], $shippingProtection['base'], $shippingProtection['base_currency']);

            $resultExtensionAttributes = $result->getExtensionAttributes();
            $resultExtensionAttributes->setShippingProtection(
                [
                    'base' => $shippingProtection['base'],
                    'base_currency' => $shippingProtection['base_currency'],
                    'price' => $shippingProtection['price'],
                    'currency' => $shippingProtection['currency'],
                    'sp_quote_id' => $shippingProtection['sp_quote_id']
                ]
            );
            $result->setExtensionAttributes($resultExtensionAttributes);
        }

        return $result;
    }

    public function afterGetList(\Magento\Sales\Model\OrderRepository $subject, $result)
    {
        $orders = $result->getItems();
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $shippingProtectionTotal = $this->shippingProtectionTotalRepository->get($order->getId(), ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID);

                if (!$shippingProtectionTotal->getData() || sizeof($shippingProtectionTotal->getData()) === 0)
                    return $result;

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
                $extensionAttributes->setExtendShippingProtectionBasePrice($shippingProtectionTotal->getShippingProtectionBasePrice());
                $extensionAttributes->setExtendShippingProtectionBaseCurrency($shippingProtectionTotal->getShippingProtectionBaseCurrency());
                $extensionAttributes->setExtendShippingProtectionPrice($shippingProtectionTotal->getShippingProtectionPrice());
                $extensionAttributes->setExtendShippingProtectionCurrency($shippingProtectionTotal->getShippingProtectionCurrency());
                $extensionAttributes->setExtendShippingProtectionQuoteId($shippingProtectionTotal->getSpQuoteId());
                $order->setExtensionAttributes($extensionAttributes);
            }
        }
        return $result;
    }
}
