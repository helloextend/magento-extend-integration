<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\ViewModel;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Service\Extend;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Extend\Integration\Service\Api\Integration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;

class EnvironmentAndExtendStoreUuid implements
    \Magento\Framework\View\Element\Block\ArgumentInterface
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
    private LoggerInterface $logger;
    private Http $request;

    public function __construct(
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder,
        LoggerInterface $logger,
        Http $request
    ) {
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
        $this->logger = $logger;
        $this->request = $request;
    }

    public function getActiveEnvironment()
    {
        $activeEnvironmentUrl = $this->activeEnvironmentURLBuilder->getIntegrationURL();
        $integrationEnv = $this->activeEnvironmentURLBuilder->getEnvironmentFromURL(
            $activeEnvironmentUrl
        );
        if (isset(self::EXTEND_CONFIG_ENVIRONMENT[$integrationEnv])) {
            return self::EXTEND_CONFIG_ENVIRONMENT[$integrationEnv];
        }
        return $integrationEnv;
    }

    public function getExtendStoreUuid(): ?string
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $integrationId = $this->scopeConfig->getValue(
                Integration::INTEGRATION_ENVIRONMENT_CONFIG
            );
            $storeIntegration = $this->storeIntegrationRepository->getByStoreIdAndIntegrationId(
                $storeId,
                $integrationId
            );
            return $storeIntegration->getExtendStoreUuid();
        } catch (\Exception $exception) {
            $this->logger->error(
                'The follow error was reported while trying to populate window.ExtendConfig: ' .
                    $exception->getMessage()
            );
            $this->integration->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error'
            );

            return '';
        }
    }

    public function isExtendProductProtectionEnabled(): bool
    {
        return $this->scopeConfig->getValue(Extend::ENABLE_PRODUCT_PROTECTION) === '1';
    }

    public function isCartBalancingEnabled(): bool
    {
        return $this->scopeConfig->getValue(Extend::ENABLE_CART_BALANCING) === '1';
    }

    public function isProductProtectionProductDisplayPageOfferEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            Extend::ENABLE_PRODUCT_PROTECTION_PRODUCT_DISPLAY_PAGE_OFFER
        ) === '1';
    }

    public function isProductProtectionCartOfferEnabled(): bool
    {
        return $this->scopeConfig->getValue(Extend::ENABLE_PRODUCT_PROTECTION_CART_OFFER) === '1';
    }

    public function isProductProtectionPostPurchaseLeadModalOfferEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            Extend::ENABLE_PRODUCT_PROTECTION_POST_PURCHASE_LEAD_MODAL_OFFER
        ) === '1';
    }

    public function isProductProtectionProductCatalogPageModalOfferEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            Extend::ENABLE_PRODUCT_PROTECTION_PRODUCT_CATALOG_PAGE_MODAL_OFFER
        ) === '1';
    }

    /**
     * Get Lead Token From Url
     *
     * @return string
     */
    public function getLeadTokenFromUrl(): string
    {
        return $this->request->getParam(Extend::LEAD_TOKEN_URL_PARAM) ?? '';
    }
}
