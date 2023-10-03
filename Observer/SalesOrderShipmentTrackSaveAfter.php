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
use Magento\Sales\Model\Order\Shipment\Track;
use Psr\Log\LoggerInterface;

class SalesOrderShipmentTrackSaveAfter extends BaseExtendObserver
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
        /** @var Track */
        $track = $observer->getEvent()->getTrack();
        /** @var Shipment */
        $shipment = $track->getShipment();
        $endpoint = [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_shipments_update'],
            'type' => 'middleware',
        ];
        $this->shipmentObserverHandler->execute($endpoint, $shipment, []);
    }
}
