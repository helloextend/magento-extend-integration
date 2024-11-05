<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Extend as ExtendService;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Psr\Log\LoggerInterface;

class AdminSystemConfigSaveExtend extends BaseExtendObserver
{
    private IntegrationServiceInterface $integrationService;
    private ScopeConfigInterface $scopeConfig;
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private OauthServiceInterface $oauthService;
    private MetadataBuilder $metadataBuilder;

    /**
     * @param LoggerInterface $logger
     * @param ExtendService $extendService
     * @param Integration $extendIntegrationService
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        ExtendService $extendService,
        Integration $extendIntegrationService,
        StoreManagerInterface $storeManager,
        integrationServiceInterface $integrationService,
        ScopeConfigInterface $scopeConfig,
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        OauthServiceInterface $oauthService,
        MetadataBuilder $metadataBuilder
    ) {
        parent::__construct($logger, $extendService, $extendIntegrationService, $storeManager);
        $this->integrationService = $integrationService;
        $this->scopeConfig = $scopeConfig;
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->oauthService = $oauthService;
        $this->metadataBuilder = $metadataBuilder;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $section = $request->getParam('section');

        // The unfortunate part of this solution is that it will fire on any system config save but we can short-circuit immediately
        // if it's not Extend.
        if ($section !== 'extend') {
            return;
        }

        // If the checkbox was manually checked in finish-integration-steps.phtml then the value should come in here as a truthy 'on'.
        // It will be null in follow-up cases where the input is disabled/checked and no value was changed
        $activateCurrentStore = $request->getParam('activate_current_store');

        if (!$activateCurrentStore) {
            return;
        }

        $extendStoreId = $request->getParam('extend_store_id');

        $currentStore = $request->getParam('store');
        $activeIntegration = $this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);
        $integration = $this->integrationService->get($activeIntegration);
        $integrationId = $integration->getId();

        $storeListForActiveIntegration = $this->storeIntegrationRepository->getListByIntegration($integrationId);
        $hasIntegration = in_array($currentStore, $storeListForActiveIntegration);

        if (!$hasIntegration) {
            $this->storeIntegrationRepository->saveStoreToIntegration($integrationId, $currentStore);
        }

        $storeIntegration = $this->storeIntegrationRepository->getByStoreIdAndIntegrationId($currentStore, $integrationId);

        // Even if the store has an integration, we also need to check if there is an error on the integration
        // if there is an error we need to re-try the integration, this will allow us to recover from an error state
        // if there is no error we return early
        if ($hasIntegration && $storeIntegration->getIntegrationError() === null) {
            return;
        }

        $oauth = $this->oauthService->loadConsumer($integration->getConsumerId());
        $store = $this->storeManager->getStore($currentStore);

        $endpoint = [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_stores_create'],
            'type' => 'middleware',
        ];

        $oauthKey = $oauth->getKey();

        if ($oauthKey && $oauth->getSecret()) {
            [$headers, $body] = $this->metadataBuilder->execute([], $endpoint, [
                'magentoStoreUuid' => $storeIntegration->getStoreUuid(),
                'magentoStoreId' => $currentStore,
                'magentoConsumerKey' => $oauthKey,
                'extendStoreId' => $extendStoreId ? $extendStoreId : $storeIntegration->getExtendStoreUuid(),
                'storeDomain' => rtrim(
                    str_replace(
                        ['https://', 'http://'],
                        '',
                        $this->scopeConfig->getValue(
                            Store::XML_PATH_UNSECURE_BASE_URL,
                            'store',
                            $currentStore
                        )
                    ),
                    '/'
                ),
                'name' => $store->getName(),
                'websiteId' => $store->getWebsiteId(),
                'weightUnit' => $this->scopeConfig->getValue(
                    Data::XML_PATH_WEIGHT_UNIT,
                    'store',
                    $currentStore
                ),
            ]);

            $response = $this->extendIntegrationService->execute($endpoint, $body, $headers, null, true);
            $this->storeIntegrationRepository->setIntegrationErrorForStoreIdAndIntegrationId(
                $currentStore,
                $integrationId,
                $response ?? null
            );
        }
    }
}
