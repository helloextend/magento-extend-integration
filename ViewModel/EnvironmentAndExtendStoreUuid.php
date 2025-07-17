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
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;

class EnvironmentAndExtendStoreUuid implements
    \Magento\Framework\View\Element\Block\ArgumentInterface
{
    private const EXTEND_CONFIG_ENVIRONMENT = [
        // This is for custom mapping of Integration environments to Extend environments
        'dev' => 'development',
        'prod' => 'production',
    ];

    /** @var StoreIntegrationRepositoryInterface */
    private $storeIntegrationRepository;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var ActiveEnvironmentURLBuilder */
    private $activeEnvironmentURLBuilder;

    /** @var LoggerInterface */
    private $logger;

    /** @var Http */
    private $request;

    /** @var Extend */
    private $extendService;

    /**
     * EnvironmentAndExtendStoreUuid constructor
     *
     * @param StoreIntegrationRepositoryInterface $storeIntegrationRepository
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder
     * @param LoggerInterface $logger
     * @param Http $request
     * @param Extend $extendService
     */
    public function __construct(
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder,
        LoggerInterface $logger,
        Http $request,
        Extend $extendService
    ) {
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
        $this->logger = $logger;
        $this->request = $request;
        $this->extendService = $extendService;
    }

    /**
     * Get Active Environment
     *
     * @return string
     */
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

    /**
     * Get Extend Store UUID
     *
     * @return ?string
     */
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

    /**
     * Determine if Extend Product Protection is currently enabled
     *
     * @return bool
     */
    public function isExtendProductProtectionEnabled(): bool
    {
        return $this->extendService->isEnabled()
            && $this->getScopedConfigValue(Extend::ENABLE_PRODUCT_PROTECTION) === '1';
    }

    /**
     * Determine if Extend Shipping Protection is currently enabled
     *
     * @return bool
     */
    public function isExtendShippingProtectionEnabled(): bool
    {
        return $this->extendService->isEnabled()
            && $this->getScopedConfigValue(Extend::ENABLE_SHIPPING_PROTECTION) === '1';
    }

    /**
     * Determine if Extend Cart Balancing is currently enabled
     *
     * @return bool
     */
    public function isCartBalancingEnabled(): bool
    {
        return $this->extendService->isEnabled() && $this->getScopedConfigValue(Extend::ENABLE_CART_BALANCING) === '1';
    }

    /**
     * Determine if Product Display Page Offer is currently enabled
     *
     * @return bool
     */
    public function isProductProtectionProductDisplayPageOfferEnabled(): bool
    {
        return $this->getScopedConfigValue(
            Extend::ENABLE_PRODUCT_PROTECTION_PRODUCT_DISPLAY_PAGE_OFFER
        ) === '1';
    }

    /**
     * Determine if Cart Offer is currently enabled
     *
     * @return bool
     */
    public function isProductProtectionCartOfferEnabled(): bool
    {
        return $this->getScopedConfigValue(Extend::ENABLE_PRODUCT_PROTECTION_CART_OFFER) === '1';
    }

    /**
     * Determine if Minicart Offer is currently enabled
     *
     * @return bool
     */
    public function isProductProtectionMinicartOfferEnabled(): bool
    {
        return $this->getScopedConfigValue(Extend::ENABLE_PRODUCT_PROTECTION_MINICART_OFFER) ===
            '1';
    }

    /**
     * Determine if Lead Modal Offer is currently enabled
     *
     * @return bool
     */
    public function isProductProtectionPostPurchaseLeadModalOfferEnabled(): bool
    {
        return $this->getScopedConfigValue(
            Extend::ENABLE_PRODUCT_PROTECTION_POST_PURCHASE_LEAD_MODAL_OFFER
        ) === '1';
    }

    /**
     * Determine if Product Catalog Page Modal Offer is currently enabled
     *
     * @return bool
     */
    public function isProductProtectionProductCatalogPageModalOfferEnabled(): bool
    {
        return $this->getScopedConfigValue(
            Extend::ENABLE_PRODUCT_PROTECTION_PRODUCT_CATALOG_PAGE_MODAL_OFFER
        ) === '1';
    }

    /**
     * Get Scoped Config Value
     *
     * @param string $configPath
     * @return string
     */
    private function getScopedConfigValue(string $configPath): string
    {
        $scopeCode = $this->storeManager->getStore()->getCode();
        $scopeType = ScopeInterface::SCOPE_STORES;
        return $this->scopeConfig->getValue($configPath, $scopeType, $scopeCode) ?: '';
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

    /**
     * Get the current selected Currency Code
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Determine if the currently selected currency should result in the offer being rendered.
     *
     * @return boolean
     */
    public function isCurrencySupported(): bool
    {
        try {
            $currentCurrency = $this->getCurrencyCode();
            $baseCurrency = $this->storeManager->getStore()->getBaseCurrency()->getCode();

            $isSameAsBaseCurrency = $currentCurrency === $baseCurrency;
            $isSupported = in_array($currentCurrency, Extend::SUPPORTED_CURRENCIES);

            return $isSameAsBaseCurrency && $isSupported;
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error determining if selected currency matches store base currency. Falling back to true.' .
                    $exception->getMessage()
            );
            return true;
        }
    }
}
