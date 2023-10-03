<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class SalesOrderSaveAfter extends BaseExtendObserver
{
    /**
     * @var OrderObserverHandler
     */
    private $orderObserverHandler;

    /**
     * @param LoggerInterface $logger
     * @param ExtendService $extendService
     * @param Integration $extendIntegrationService
     * @param StoreManagerInterface $storeManager
     * @param ProductObserverHandler $productObserverHandler
     */
    public function __construct(
        LoggerInterface $logger,
        ExtendService $extendService,
        Integration $extendIntegrationService,
        StoreManagerInterface $storeManager,
        OrderObserverHandler $orderObserverHandler
    ) {
        parent::__construct($logger, $extendService, $extendIntegrationService, $storeManager);
        $this->orderObserverHandler = $orderObserverHandler;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $orderCreatedAt = $order->getCreatedAt();
        $orderUpdatedAt = $order->getUpdatedAt();

        if ($orderCreatedAt !== $orderUpdatedAt) {
            $this->orderObserverHandler->execute(
                [
                    'path' =>
                        Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_update'],
                    'type' => 'middleware',
                ],
                $order,
                []
            );
        }
    }
}
