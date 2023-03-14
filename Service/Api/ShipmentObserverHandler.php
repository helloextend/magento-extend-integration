<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Shipment;
use \Psr\log\LoggerInterface;

class ShipmentObserverHandler extends BaseObserverHandler
{

    public function __construct(
        LoggerInterface $logger,
        Integration $integration,
        StoreManagerInterface $storeManager,
        MetadataBuilder $metadataBuilder
    ) {
        parent::__construct(
            $logger,
            $integration,
            $storeManager,
            $metadataBuilder
        );
    }

    /**
     * @param array $integrationEndpoint
     * @param Shipment $shipment
     * @param array $additionalFields
     * @return void
     */
    public function execute(array $integrationEndpoint, Shipment $shipment, array $additionalFields)
    {
        try {
            $shipmentId = $shipment->getId();
            $shipmentOrder = $shipment->getOrder();
            $orderId = $shipmentOrder->getId();

            $data = array_merge(['shipment_id' => $shipmentId, 'order_id' => $orderId], $additionalFields);

            [$headers, $body] = $this->metadataBuilder->execute([$shipmentOrder->getStoreId()], $integrationEndpoint, $data);

            $this->integration->execute(
                $integrationEndpoint,
                $body,
                $headers
            );
        } catch (\Exception $exception) {
            // silently handle errors
            $this->logger->error('Extend Shipment Observer encountered the following error: ' . $exception->getMessage());
            $this->integration->logErrorToLoggingService($exception->getMessage(), $this->storeManager->getStore()->getId(), 'error');
        }
    }
}
