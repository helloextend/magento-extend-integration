<?php
/*
 * Copyright Extend (c) 2024. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Extend\Integration\Api\HealthCheckInterface;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Extend\Integration\Api\Data\HealthCheckResponseInterface;
use Magento\Integration\Model\IntegrationService;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Controller\Result\JsonFactory;

class HealthCheck implements HealthCheckInterface
{

    /**
     * @var IntegrationService
     */
    private IntegrationService $integrationService;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var MetadataBuilder
     */
    private MetadataBuilder $metadataBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var ActiveEnvironmentURLBuilder
     */
    private ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder;

    /**
     * @var OauthServiceInterface
     */
    private OauthServiceInterface $oauthService;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var HealthCheckResponseInterface
     */
    private HealthCheckResponseInterface $healthCheckResponse;

    /**
     * @param Context $context
     * @param IntegrationService $integrationService
     * @param ManagerInterface $messageManager
     * @param MetadataBuilder $metadataBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder
     * @param OauthServiceInterface $oauthService
     * @param JsonFactory $resultJsonFactory
     * @param Curl $curl
     * @param HealthCheckResponseInterface $healthCheckResponse
     */
    public function __construct(
        IntegrationService $integrationService,
        ManagerInterface $messageManager,
        MetadataBuilder $metadataBuilder,
        ScopeConfigInterface $scopeConfig,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder,
        OauthServiceInterface $oauthService,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        HealthCheckResponseInterface $healthCheckResponse
    ) {
        $this->integrationService = $integrationService;
        $this->messageManager = $messageManager;
        $this->metadataBuilder = $metadataBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
        $this->oauthService = $oauthService;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        $this->healthCheckResponse = $healthCheckResponse;
    }

    /**
     * Executes an authenticated request to the Extend API to check the health of the integration
     * and returns the status code of the request to the caller.
     *
     * This is basically a proxy. We could make this request directly from a client-side script and skip
     * this endpoint, but we've built it this way so that we don't have to expose the auth token to the client.
     *
     * @return HealthCheckResponseInterface
     */
    public function check()
    {
        try {
            $activeIntegration = $this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);
            $integration = $this->integrationService->get($activeIntegration);

            $consumerId = $integration->getConsumerId();
            $oauthConsumerKey = $this->oauthService
              ->loadConsumer($consumerId)
              ->getKey();

            $endpoint = [
                'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['integration_health_check'],
                'type' => 'middleware'
            ];

            [$headers] = $this->metadataBuilder->execute([], $endpoint, []);

            $this->curl->setHeaders($headers);
            $url = $this->activeEnvironmentURLBuilder->getIntegrationURL() . $endpoint['path'] . '/' . $oauthConsumerKey;
            $this->curl->get($url);
            $code = $this->curl->getStatus();
            $this->healthCheckResponse->setCode($code);
            return $this->healthCheckResponse;
        } catch (\Exception $exception) {
            $this->healthCheckResponse->setCode(500);
            $this->healthCheckResponse->setMessage($exception->getMessage());
            return $this->healthCheckResponse;
        }
    }
}
