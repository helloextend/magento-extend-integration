<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class SalesOrderSaveAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderObserverHandler
     */
    private $orderObserverHandler;

    /**
     * @var Integration
     */
    private $integration;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        LoggerInterface $logger,
        OrderObserverHandler $orderObserverHandler,
        Integration $integration,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->orderObserverHandler = $orderObserverHandler;
        $this->integration = $integration;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $orderCreatedAt = $order->getCreatedAt();
        $orderUpdatedAt = $order->getUpdatedAt();

        if ($orderCreatedAt !== $orderUpdatedAt) {
            try {
                $this->orderObserverHandler->execute(
                    [
                        'path' =>
                            Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_update'],
                        'type' => 'middleware',
                    ],
                    $order,
                    []
                );
            } catch (\Exception $exception) {
                // silently handle errors
                $this->logger->error(
                    'Extend Order Observer Handler encountered the following error: ' .
                        $exception->getMessage()
                );
                $this->integration->logErrorToLoggingService(
                    $exception->getMessage(),
                    $this->storeManager->getStore()->getId(),
                    'error'
                );
            }
        }
    }
}
