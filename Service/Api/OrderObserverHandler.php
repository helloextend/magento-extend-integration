<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Logger\ExtendOrders as ExtendOrdersLogger;
use Extend\Integration\Model\Config\Source\OrderLogLevel;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Psr\log\LoggerInterface;

class OrderObserverHandler extends BaseObserverHandler
{
    /**
     * @var ExtendOrdersLogger
     */
    private ExtendOrdersLogger $extendOrdersLogger;

    /**
     * @var ExtendService
     */
    private ExtendService $extendService;

    public function __construct(
        LoggerInterface $logger,
        Integration $integration,
        StoreManagerInterface $storeManager,
        MetadataBuilder $metadataBuilder,
        ExtendOrdersLogger $extendOrdersLogger,
        ExtendService $extendService
    ) {
        parent::__construct($logger, $integration, $storeManager, $metadataBuilder);
        $this->extendOrdersLogger = $extendOrdersLogger;
        $this->extendService = $extendService;
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
        $loggingEnabled = $this->extendService->isOrderLoggingEnabled();
        $logLevel = $this->extendService->getOrderLogLevel();
        $endpoint = $integrationEndpoint['path'] ?? '';
        $orderId = $order->getId();

        try {
            $orderStatus = $order->getStatus();
            $orderArray = [
                'order_id' => $orderId,
                'order_status' => $orderStatus,
                'additional_fields' => $additionalFields,
            ];

            if ($loggingEnabled && $logLevel === OrderLogLevel::PAYLOADS_AND_ERRORS) {
                $this->extendOrdersLogger->info('Extend order webhook dispatching', [
                    'endpoint' => $endpoint,
                    'order_id' => $orderId,
                    'order_status' => $orderStatus,
                    'additional_fields' => $additionalFields,
                ]);
            }

            [$headers, $body] = $this->metadataBuilder->execute(
                [$order->getStoreId()],
                $integrationEndpoint,
                $orderArray
            );

            if ($loggingEnabled && $logLevel === OrderLogLevel::VERBOSE) {
                $this->extendOrdersLogger->info('Extend order webhook dispatching', [
                    'endpoint' => $endpoint,
                    'order_id' => $orderId,
                    'order_status' => $orderStatus,
                    'additional_fields' => $additionalFields,
                    'request_body' => $body,
                ]);
            }

            $responseBody = $this->integration->execute($integrationEndpoint, $body, $headers, true);

            if ($loggingEnabled && $logLevel === OrderLogLevel::VERBOSE) {
                $this->extendOrdersLogger->info('Extend order webhook API response', [
                    'endpoint' => $endpoint,
                    'order_id' => $orderId,
                    'response' => $responseBody,
                ]);
            }
        } catch (\Exception $exception) {
            if ($loggingEnabled) {
                $this->extendOrdersLogger->error('Extend order webhook failed', [
                    'endpoint' => $endpoint,
                    'order_id' => $orderId,
                    'error' => $exception->getMessage(),
                ]);
            }

            // silently handle errors
            $this->logger->error(
                'Extend Order Observer encountered the following error: ' . $exception->getMessage()
            );
            $this->integration->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error',
                $exception
            );
        }
    }
}
