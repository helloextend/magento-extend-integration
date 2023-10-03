<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\ShipmentObserverHandler;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;

class SalesOrderShipmentSaveAfter extends BaseExtendObserver
{
    /**
     * @var ShipmentObserverHandler
     */
    private $shipmentObserverHandler;

    /**
     * @param LoggerInterface $logger
     * @param ExtendService $extendService
     * @param Integration $extendIntegrationService
     * @param StoreManagerInterface $storeManager
     * @param ShipmentObserverHandler $shipmentObserverHandler
     */
    public function __construct(
        LoggerInterface $logger,
        ExtendService $extendService,
        Integration $extendIntegrationService,
        StoreManagerInterface $storeManager,
        ShipmentObserverHandler $shipmentObserverHandler
    ) {
        parent::__construct($logger, $extendService, $extendIntegrationService, $storeManager);
        $this->shipmentObserverHandler = $shipmentObserverHandler;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $endpoint = $this->resolveEndpoint($shipment);
        $this->shipmentObserverHandler->execute($endpoint, $shipment, []);
    }

    /**
     * @param Shipment $shipment
     * @return array
     */
    private function resolveEndpoint($shipment): array
    {
        $createdAt = $shipment->getCreatedAt();
        $updatedAt = $shipment->getUpdatedAt();

        if ($createdAt === $updatedAt) {
            return [
                'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_shipments_create'],
                'type' => 'middleware',
            ];
        }

        return [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_shipments_update'],
            'type' => 'middleware',
        ];
    }
}
