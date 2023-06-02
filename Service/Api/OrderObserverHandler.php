<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Psr\log\LoggerInterface;

class OrderObserverHandler extends BaseObserverHandler
{
    public function __construct(
        LoggerInterface $logger,
        Integration $integration,
        StoreManagerInterface $storeManager,
        MetadataBuilder $metadataBuilder
    ) {
        parent::__construct($logger, $integration, $storeManager, $metadataBuilder);
    }

    /**
     * @param array $integrationEndpoint
     * @param Order $order
     * @param array $additionalFields
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(array $integrationEndpoint, Order $order, array $additionalFields)
    {
        try {
            $orderId = $order->getId();
            $orderStatus = $order->getStatus();
            $orderArray = [
                'order_id' => $orderId,
                'order_status' => $orderStatus,
                'additional_fields' => $additionalFields,
            ];

            [$headers, $body] = $this->metadataBuilder->execute(
                [$order->getStoreId()],
                $integrationEndpoint,
                $orderArray
            );

            $this->integration->execute($integrationEndpoint, $body, $headers);
        } catch (\Exception $exception) {
            // silently handle errors
            $this->logger->error(
                'Extend Order Observer encountered the following error: ' . $exception->getMessage()
            );
            $this->integration->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error'
            );
        }
    }
}
