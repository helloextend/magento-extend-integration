<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\ViewModel;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Extend\Integration\Service\Api\Integration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class EnvironmentAndExtendStoreUuid implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    const EXTEND_CONFIG_ENVIRONMENT = [
        // This is for custom mapping of Integration environments to Extend environments
        'dev' => 'development',
    ];

    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private StoreManagerInterface $storeManager;
    private ScopeConfigInterface $scopeConfig;
    private ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder;

    public function __construct(
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder
    ) {
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
    }

    public function getActiveEnvironment()
    {
        $activeEnvironmentUrl = $this->activeEnvironmentURLBuilder->getIntegrationURL();
        $integrationEnv = $this->activeEnvironmentURLBuilder->getEnvironmentFromURL($activeEnvironmentUrl);
        if (isset(self::EXTEND_CONFIG_ENVIRONMENT[$integrationEnv])) {
            return self::EXTEND_CONFIG_ENVIRONMENT[$integrationEnv];
        }
        return $integrationEnv;
    }

    public function getExtendStoreUuid(): ?string
    {
        $storeId = $this->storeManager->getStore()->getId();
        $integrationId = $this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);
        $storeIntegration = $this->storeIntegrationRepository->getByStoreIdAndIntegrationId($storeId, $integrationId);
        return $storeIntegration->getExtendStoreUuid();
    }
}
