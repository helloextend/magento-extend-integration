<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class CreditmemoRefund implements ObserverInterface
{
    /**
     * @var \Psr\log\LoggerInterface
     */
    private $logger;
    private OrderObserverHandler $orderObserverHandler;
    private Integration $integration;
    private StoreManagerInterface $storeManager;

    public function __construct(
        \Psr\log\LoggerInterface $logger,
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
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $this->orderObserverHandler->execute(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_cancel'],
                    'type' => 'middleware',
                ],
                $observer->getCreditmemo()->getOrder(),
                ['credit_memo_id' => $observer->getCreditmemo()->getId()]
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
