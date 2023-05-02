<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\ShipmentObserverHandler;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;

class SalesOrderShipmentSaveAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShipmentObserverHandler
     */
    private $shipmentObserverHandler;

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
        ShipmentObserverHandler $shipmentObserverHandler,
        Integration $integration,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->shipmentObserverHandler = $shipmentObserverHandler;
        $this->integration = $integration;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $shipment = $observer->getEvent()->getShipment();
            $endpoint = $this->resolveEndpoint($shipment);
            $this->shipmentObserverHandler->execute($endpoint, $shipment, []);
        } catch (\Exception $exception) {
            // silently handle errors
            $this->logger->error(
                'Extend Shipment Observer Handler encountered the following error: ' .
                    $exception->getMessage()
            );
            $this->integration->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error'
            );
        }
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
