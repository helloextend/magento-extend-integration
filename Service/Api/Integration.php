<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Integration
{
    const INTEGRATION_ENVIRONMENT_CONFIG = 'extend/integration/environment';

    const EXTEND_INTEGRATION_ENDPOINTS = [
        'webhooks_orders_create' => '/webhooks/orders/create',
        'webhooks_orders_cancel' => '/webhooks/orders/cancel',
        'webhooks_orders_update' => '/webhooks/orders/update',
        'webhooks_shipments_create' => '/webhooks/shipments/create',
        'webhooks_shipments_update' => '/webhooks/shipments/update',
        'webhooks_products_create' => '/webhooks/products/create',
        'webhooks_products_update' => '/webhooks/products/update',
        'webhooks_products_delete' => '/webhooks/products/delete',
        'webhooks_stores_create' => '/webhooks/stores/create',
        'log_error' => '/module/logging',
        'app_uninstall' => '/app/uninstall',
    ];

    const EXTEND_SDK_ENDPOINTS = [
        'shipping_offers_quotes' => '/shipping-offers/quotes',
    ];

    private Curl $curl;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private StoreManagerInterface $storeManager;
    private ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder;
    private AccessTokenBuilder $accessTokenBuilder;

    public function __construct(
        Curl $curl,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder,
        AccessTokenBuilder $accessTokenBuilder
    ) {
        $this->curl = $curl;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
        $this->accessTokenBuilder = $accessTokenBuilder;
    }

    /**
     * Every Extend observer will use this class to call the Extend integration, providing the endpoint to be called and the payload to be received by the integration.
     *
     * @param array $endpoint
     * @param array $data
     * @param array $headers
     * @param null $getBody
     * @return void|string
     * @throws NoSuchEntityException
     */
    public function execute(array $endpoint, array $data, array $headers, $getBody = null)
    {
        try {
            $this->curl->setHeaders($headers);

            $fullUrl = $this->activeEnvironmentURLBuilder->getIntegrationURL() . $endpoint['path'];
            $payload = json_encode($data);

            $this->curl->post($fullUrl, $payload);

            $status = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $response = $status . ' ' . $responseBody;

            $this->logger->info(
                'Curl request to ' . $fullUrl . ' provided the following response: ' . $response
            );

            if ($status >= 400) {
                $errorMessage =
                    'Curl request to ' .
                    $fullUrl .
                    ' provided the following unsuccessful response: ' .
                    $response;

                $this->logErrorToLoggingService(
                    $errorMessage,
                    $this->storeManager->getStore()->getId(),
                    'error'
                );
            }

            if ($getBody) {
                return $responseBody;
            }
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $this->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error'
            );
        }
    }

    public function logErrorToLoggingService($message, $storeId, $logLevel)
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'X-Extend-Access-Token' => $this->accessTokenBuilder->getAccessToken(),
            ];

            $this->curl->setHeaders($headers);

            $body = $this->serializer->serialize([
                'message' => $message,
                'store_id' => $storeId,
                'timestamp' => time(),
                'log_level' => $logLevel,
            ]);

            $endpoint =
                $this->activeEnvironmentURLBuilder->getIntegrationURL() .
                self::EXTEND_INTEGRATION_ENDPOINTS['log_error'];

            $this->curl->post($endpoint, $body);
        } catch (\Exception $exception) {
            $this->logger->error('Cannot log to logging service: ' . $exception->getMessage());
        }
    }
}
