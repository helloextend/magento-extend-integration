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

        // If the checkbox was manually checked in finish-integration.phtml then the value should come in here as a truthy 'on'.
        // It will be null in follow-up cases where the input is disabled/checked and no value was changed
        $activateCurrentStore = $request->getParam('activate_current_store');

        if (!$activateCurrentStore) {
          return;
        }

        $currentStore = $request->getParam('store');
        $activeIntegration = $this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);
        $integration = $this->integrationService->get($activeIntegration);
        $integrationId = $integration->getId();

        $storeListForActiveIntegration = $this->storeIntegrationRepository->getListByIntegration($integrationId);

        // This check *should* be unnecessary as it's done on page-load to determine whether the checkbox should be
        // active but in a race condition case it may prevent unnecessary requests
        if (in_array($currentStore, $storeListForActiveIntegration)) {
          return;
        }

        $this->storeIntegrationRepository->saveStoreToIntegration(
          $integrationId,
          $currentStore
        );

        $integrationStore = $this->storeIntegrationRepository->getByStoreIdAndIntegrationId(
          $currentStore,
          $integrationId
        );

        $oauth = $this->oauthService->loadConsumer($integration->getConsumerId());
        $store = $this->storeManager->getStore($currentStore);

        $endpoint = [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_stores_create'],
            'type' => 'middleware',
        ];

        $oauthKey = $oauth->getKey();

        if ($oauthKey && $oauth->getSecret()) {
            [$headers, $body] = $this->metadataBuilder->execute([], $endpoint, [
                'magentoStoreUuid' => $integrationStore->getStoreUuid(),
                'magentoStoreId' => $currentStore,
                'magentoConsumerKey' => $oauthKey,
                'extendStoreId' => $integrationStore->getExtendStoreUuid(),
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

            $this->extendIntegrationService->execute($endpoint, $body, $headers);
        }
    }
}
