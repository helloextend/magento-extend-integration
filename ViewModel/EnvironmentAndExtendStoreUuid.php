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
        'prod' => 'production',
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
        return 'platformsandbox';
    }

    public function getExtendStoreUuid(): ?string
    {
        return '12345678-1234-1234-1234-123456789012';
    }
}
