<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Psr\Log\LoggerInterface;

class ActiveEnvironmentURLBuilder
{
    const INTEGRATION_ENVIRONMENT_CONFIG = 'extend/integration/environment';

    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private IntegrationServiceInterface $integrationService;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        IntegrationServiceInterface $integrationService
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->integrationService = $integrationService;
    }

    /**
     * Grabs the integration initialization endpoint from the store configuration setting for which environment is currently active
     * and strips the off the initialization specific parts resulting in the base url for all the webhook endpoints.
     *
     * @return string
     */
    public function getIntegrationURL(): string
    {
        try {
            $integrationId = $this->scopeConfig->getValue(self::INTEGRATION_ENVIRONMENT_CONFIG);
            $integration = $this->integrationService->get($integrationId);
            $endpoint = $integration->getEndpoint();
            $urlParts = explode('/auth/start', $endpoint);

            return $urlParts[0];
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return '';
        }
    }

    /**
     * Grabs the environment specific integration url and maps it to the api url for the particular environment.
     *
     * @return string
     */
    public function getApiURL(): string
    {
        $integrationURL = $this->getIntegrationURL();

        if (empty($integrationURL)) {
            return '';
        }

        $environment = self::isProductionURL($integrationURL)
            ? ''
            : '-' . self::getEnvironmentFromURL($integrationURL);

        return 'https://api' . $environment . '.helloextend.com';
    }

    private function isProductionURL(string $url): bool
    {
        return $this->getEnvironmentFromURL($url) === 'prod';
    }

    public function getEnvironmentFromURL(?string $url): string
    {
        if ($url !== null && str_contains($url, 'https://integ-mage-')) {
            $urlParts = explode('https://integ-mage-', $url);
            $urlParts = explode('.', $urlParts[1]);
            return $urlParts[0];
        }
        if ($url !== null && str_contains($url, 'https://integ-mage.')) {
            return 'prod';
        }
        return '';
    }
}
