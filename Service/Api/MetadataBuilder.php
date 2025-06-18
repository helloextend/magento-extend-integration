<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Exception;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ProductMetadataInterface;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Extend\Integration\Service\Api\Integration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;


class MetadataBuilder
{
    private IdentityService $identityService;
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private ProductMetadataInterface $productMetadata;
    private AccessTokenBuilder $accessTokenBuilder;
    private ComposerInformation $composerInformation;
    private IntegrationServiceInterface $integrationService;
    private OauthServiceInterface $oauthService;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;

    public function __construct(
        IdentityService $identityService,
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        ProductMetadataInterface $productMetadata,
        AccessTokenBuilder $accessTokenBuilder,
        ComposerInformation $composerInformation,
        IntegrationServiceInterface $integrationService,
        OauthServiceInterface $oauthService,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->identityService = $identityService;
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->productMetadata = $productMetadata;
        $this->accessTokenBuilder = $accessTokenBuilder;
        $this->composerInformation = $composerInformation;
        $this->oauthService = $oauthService;
        $this->integrationService = $integrationService;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param array $storeIds
     * @param array $integrationEndpoint
     * @param array $data
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(array $storeIds, array $integrationEndpoint, array $data): array
    {
        // storeIds are typed as int|null upstream, so we need to filter out null values
        $storeIds = array_filter($storeIds, fn ($storeId) => $storeId !== null);

        $headers = [];
        $body = [];

        $headers['X-Extend-Access-Token'] = $this->accessTokenBuilder->getAccessToken();
        $headers['Content-Type'] = 'application/json';
        $fullMagentoVersion = $this->productMetadata->getVersion();
        $trimmedMagentoVersion = strstr($fullMagentoVersion, '-', true);
        $headers['X-Magento-Version'] = !$trimmedMagentoVersion ? $fullMagentoVersion : $trimmedMagentoVersion;

        try {
            $installedMagentoPackages = $this->composerInformation->getInstalledMagentoPackages();

            if (!empty($installedMagentoPackages['helloextend/integration']['version'])) {
                $headers['X-Extend-Mage-Module-Version'] = ($installedMagentoPackages['helloextend/integration']['version']);
            }

            $activeIntegration = $this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);
            $integration = $this->integrationService->get($activeIntegration);
            $oauth = $this->oauthService->loadConsumer($integration->getConsumerId());

            $headers['X-Extend-Mage-Consumer-Key'] = $oauth->getKey();
            // We are only including the storeIds in the header when there is only one store, otherwise this list may get too long
            if (count($storeIds) === 1) {
                $headers['X-Extend-Mage-Store-UUID'] = $this->storeIntegrationRepository
                    ->getByStoreIdAndActiveEnvironment($storeIds[0])
                    ->getStoreUuid();
            }
        } catch (Exception $exception) {
            // silently fails
            $this->logger->error('The follow error was reported while trying to add additional metadata headers: ' . $exception->getMessage());
        }


        $body['webhook_id'] = $this->identityService->generateId();
        $body['webhook_created_at'] = time();
        $body['topic'] = str_replace('/webhooks/', '', $integrationEndpoint['path']);
        $body['data'] = $data;

        foreach ($storeIds as $storeId) {
            $body['magento_store_uuids'][] =
                $this->storeIntegrationRepository
                    ->getByStoreIdAndActiveEnvironment($storeId)
                    ->getStoreUuid();
        }

        return [$headers, $body];
    }
}
