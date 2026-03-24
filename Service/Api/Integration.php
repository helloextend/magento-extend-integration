<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Logger\ExtendIntegration as IntegrationLogger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
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
        'webhooks_categories_create' => '/webhooks/categories/create',
        'webhooks_categories_update' => '/webhooks/categories/update',
        'webhooks_categories_delete' => '/webhooks/categories/delete',
        'webhooks_stores_create' => '/webhooks/stores/create',
        'log_error' => '/module/logging',
        'app_uninstall' => '/app/uninstall',
        'integration_health_check' => '/integration-health-check'
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
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private IntegrationLogger $integrationLogger;

    public function __construct(
        Curl $curl,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder,
        AccessTokenBuilder $accessTokenBuilder,
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        IntegrationLogger $integrationLogger
    ) {
        $this->curl = $curl;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
        $this->accessTokenBuilder = $accessTokenBuilder;
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->integrationLogger = $integrationLogger;
    }

    /**
     * Every Extend observer will use this class to call the Extend integration, providing the endpoint to be called and the payload to be received by the integration.
     *
     * @param array $endpoint
     * @param array $data
     * @param array $headers
     * @param null $getBody
     * @param null $getError
     * @return void|string
     * @throws NoSuchEntityException
     */
    public function execute(array $endpoint, array $data, array $headers, $getBody = null, $getError = null)
    {
        try {
            $this->curl->setHeaders($headers);

            $fullUrl = $this->activeEnvironmentURLBuilder->getIntegrationURL() . $endpoint['path'];
            $payload = json_encode($data);

            $this->integrationLogger->info('Sending request to Extend API', [
                'endpoint' => $endpoint['path'],
                'created' => $data['created'] ?? null,
                'changed' => $data['changed'] ?? null,
            ]);

            $this->curl->post($fullUrl, $payload);

            $status = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $response = $status . ' ' . $responseBody;

            $this->logger->info(
                'Curl request to ' . $fullUrl . ' provided the following response: ' . $response
            );

            $this->integrationLogger->info('Received response from Extend API', [
                'endpoint' => $endpoint['path'],
                'status' => $status,
                'response_body' => $responseBody,
            ]);

            if ($status >= 400) {
                $errorMessage =
                    'Curl request to ' .
                    $fullUrl .
                    ' provided the following unsuccessful response: ' .
                    $response;

                $this->integrationLogger->error('Extend API request failed', [
                    'endpoint' => $endpoint['path'],
                    'status' => $status,
                    'response_body' => $responseBody,
                ]);

                $this->logErrorToLoggingService(
                    $errorMessage,
                    $this->storeManager->getStore()->getId(),
                    'error'
                );

                if ($getError) {
                    return 'ERROR: Integration Error with status: ' . $status;
                }
            }

            if ($getBody) {
                return $responseBody;
            }
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $this->integrationLogger->critical('Exception during Extend API request', [
                'endpoint' => $endpoint['path'] ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
            $this->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error',
                $exception
            );
        }
    }

    /**
     * Log error to Extend logging service
     *
     * @param string $message
     * @param int $storeId
     * @param string $logLevel
     * @param \Throwable|null $exception
     * @return void
     */
    public function logErrorToLoggingService($message, $storeId, $logLevel, ?\Throwable $exception = null)
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'X-Extend-Access-Token' => $this->accessTokenBuilder->getAccessToken(),
            ];

            $this->curl->setHeaders($headers);
            $ids = $this->getPreferredStoreIdentifier($storeId);

            // Build payload with separate message and stack trace
            $payload = [
                'message' => $message,
                'extend_store_id' => $ids['extend_store_uuid'],
                'magento_store_id' => $ids['magento_store_uuid'],
                'timestamp' => time(),
                'log_level' => $logLevel,
            ];

            // Add exception details if provided
            if ($exception instanceof \Throwable) {
                $payload['exception_message'] = $exception->getMessage();
                $payload['stack_trace'] = $exception->getTraceAsString();
            }

            $body = $this->serializer->serialize($payload);

            $endpoint =
                $this->activeEnvironmentURLBuilder->getIntegrationURL() .
                self::EXTEND_INTEGRATION_ENDPOINTS['log_error'];

            $this->curl->post($endpoint, $body);

            $loggingStatus = $this->curl->getStatus();
            $loggingResponseBody = $this->curl->getBody();

            if ($loggingStatus >= 400) {
                $this->logger->error('Extend logging service returned an error', [
                    'status' => $loggingStatus,
                    'response_body' => $loggingResponseBody,
                ]);
            }
        } catch (\Exception $exception) {
            $this->logger->error('Cannot log to logging service: ' . $exception->getMessage());
        }
    }

    private function getPreferredStoreIdentifier($storeId)
    {
        $extendStoreId = '';
        $magentoStoreUuid = '';
        try {
            $storeIntegration = $this->storeIntegrationRepository->getByStoreIdAndActiveEnvironment($storeId);
            if ($storeIntegration) {
                $extendStoreId = (string)($storeIntegration->getExtendStoreUuid() ?: '');
                $magentoStoreUuid = (string)($storeIntegration->getStoreUuid() ?: '');
            }
        } catch (NoSuchEntityException $exception) {
            // Store integration not found - expected for stores that haven't completed Extend setup.
            // This is a configuration issue, not a system error. Empty UUIDs will be sent to logging service,
            // allowing logs to be tracked by numeric store ID until configuration is complete.
            $this->logger->warning('Store integration not configured for store ID ' . $storeId . '. Stack trace: ' . $exception->getTraceAsString());
        } catch (\Exception $exception) {
            // Unexpected error retrieving store integration
            $this->logger->error('Error retrieving store integration for store ID ' . $storeId . ': ' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
        return [
            'extend_store_uuid' => $extendStoreId,
            'magento_store_uuid' => $magentoStoreUuid,
        ];
    }
}
