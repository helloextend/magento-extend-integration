<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Extend\Integration\Model\ResourceModel\StoreIntegration as StoreIntegrationResource;
use Extend\Integration\Model\ResourceModel\StoreIntegration\Collection;
use Extend\Integration\Model\ResourceModel\StoreIntegration\CollectionFactory;
use Extend\Integration\Model\StoreIntegrationFactory;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Extend\Integration\Service\Api\Integration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Store\Model\StoreManager;

class StoreIntegrationRepository implements \Extend\Integration\Api\StoreIntegrationRepositoryInterface
{
    const LEGACY_EXTEND_MODULE_PRODUCTION_STORE_ID = 'warranty/authentication/store_id';
    const LEGACY_EXTEND_MODULE_SANDBOX_STORE_ID = 'warranty/authentication/sandbox_store_id';

    private StoreManager $storeManager;
    private IdentityService $identityService;
    private StoreIntegrationFactory $storeIntegrationFactory;
    private StoreIntegrationResource $storeIntegrationResource;
    private OauthServiceInterface $oauthService;
    private IntegrationServiceInterface $integrationService;
    private CollectionFactory $storeIntegrationCollectionFactory;
    private ScopeConfigInterface $scopeConfig;
    private EncryptorInterface $encryptor;
    private ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder;

    public function __construct(
        StoreManager                $storeManager,
        IdentityService             $identityService,
        StoreIntegrationFactory     $storeIntegrationFactory,
        StoreIntegrationResource    $storeIntegrationResource,
        OauthServiceInterface       $oauthService,
        IntegrationServiceInterface $integrationService,
        CollectionFactory           $storeIntegrationCollectionFactory,
        ScopeConfigInterface        $scopeConfig,
        EncryptorInterface          $encryptor,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->identityService = $identityService;
        $this->storeIntegrationFactory = $storeIntegrationFactory;
        $this->storeIntegrationResource = $storeIntegrationResource;
        $this->oauthService = $oauthService;
        $this->integrationService = $integrationService;
        $this->storeIntegrationCollectionFactory = $storeIntegrationCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
    }

    /**
     * Get store UUIDs with store code and active environment (integration)
     *
     * @param string $code
     * @return StoreIntegrationInterface
     * @throws NoSuchEntityException
     */
    public function getByStoreCodeAndActiveEnvironment(string $code): StoreIntegrationInterface
    {
        $storeId = $this->storeManager->getStore($code)->getId();

        return $this->getByStoreIdAndActiveEnvironment($storeId);
    }

    /**
     * Get store UUIDs with store id and active environment (integration)
     *
     * @param int $storeId
     * @return StoreIntegrationInterface
     * @throw NoSuchEntityException
     */
    public function getByStoreIdAndActiveEnvironment(int $storeId): StoreIntegrationInterface
    {
        $integrationId = (int)$this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);

        $integration = $this->getByStoreIdAndIntegrationId($storeId, $integrationId);

        if ($integration->getId()) {
            return $integration;
        } else {
            throw new NoSuchEntityException(__('Integration Not Found'));
        }
    }

    /**
     * Get store UUIDs with store id and active environment (integration)
     *
     * @param int $storeId
     * @param int $integrationId
     * @return StoreIntegrationInterface
     */
    public function getByStoreIdAndIntegrationId(int $storeId, int $integrationId): StoreIntegrationInterface
    {
        $storeIntegrationCollection = $this->storeIntegrationCollectionFactory->create();
        return $storeIntegrationCollection
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('integration_id', ['eq' => $integrationId])
            ->getFirstItem();
    }

    /**
     * Get store Extend UUID and store ID by Magento store UUID
     *
     * @param string $storeUuid
     * @return StoreIntegrationInterface
     */
    public function getByUuid(string $storeUuid): StoreIntegrationInterface
    {
        $storeIntegration = $this->storeIntegrationFactory->create();
        $this->storeIntegrationResource->load($storeIntegration, $storeUuid, 'store_uuid');

        return $storeIntegration;
    }

    /**
     * Get store Magento UUID and store ID by Extend store UUID
     *
     * @param string $extendStoreUuid
     * @return StoreIntegrationInterface
     */
    public function getByExtendUuid(string $extendStoreUuid): StoreIntegrationInterface
    {
        $storeIntegration = $this->storeIntegrationFactory->create();
        $this->storeIntegrationResource->load($storeIntegration, $extendStoreUuid, 'extend_store_uuid');

        return $storeIntegration;
    }

    /**
     * Get stores associated with Integration (Extend Integration(s) only)
     *
     * @param int $integrationId
     * @var Collection $storeIntegrationCollection
     * @return array
     */
    public function getListByIntegration(int $integrationId): array
    {
        $storeIntegrationCollection = $this->storeIntegrationCollectionFactory->create();

        $storeIntegrationCollection
            ->addFieldToFilter(\Extend\Integration\Api\Data\StoreIntegrationInterface::INTEGRATION_ID, $integrationId)
            ->addFieldToSelect('store_id')
            ->load();

        // Retrieved list of store ids (smallint) is converted to strings so convert it back to the expected integer type
        return array_map('intval', $storeIntegrationCollection->getColumnValues('store_id'));
    }

    /**
     * Get stores associated with Integration by Consumer Key (Extend Integration(s) only)
     *
     * @param string $consumerKey
     * @var Collection $storeIntegrationCollection
     * @return array|StoreIntegrationInterface
     */
    public function getListByConsumerKey(string $consumerKey): array
    {
        $consumer = $this->oauthService->loadConsumerByKey($consumerKey);

        if (!$consumer->getId()) {
            return [];
        }

        return $this->getListByIntegration($consumer->getId());
    }

    /**
     * Link store to integration
     *
     * @param int $integrationId
     * @param int $storeId
     * @return void
     * @throws AlreadyExistsException
     */
    public function saveStoreToIntegration(int $integrationId, int $storeId): void
    {
        $storeIntegration = $this->getByStoreIdAndIntegrationId($storeId, $integrationId);
        if ($storeIntegration->getData('disabled') == 1) {
            $storeIntegration->setDisabled(0);
            $this->storeIntegrationResource->save($storeIntegration);
        } else {
            $storeCode = $this->storeManager->getStore($storeId)->getCode();
            $legacyExtendProductionStoreId = $this->scopeConfig->getValue(self::LEGACY_EXTEND_MODULE_PRODUCTION_STORE_ID, 'store', $storeCode);
            $legacyExtendSandboxStoreId = $this->scopeConfig->getValue(self::LEGACY_EXTEND_MODULE_SANDBOX_STORE_ID, 'store', $storeCode);
            $integration = $this->integrationService->get($integrationId);
            $environment = $this->activeEnvironmentURLBuilder->getEnvironmentFromURL($integration->getEndpoint());
            $storeIntegration = $this->storeIntegrationFactory->create();
            if ($legacyExtendProductionStoreId && $environment == 'prod') {
                $storeIntegration->setExtendStoreUuid($legacyExtendProductionStoreId);
            }
            if ($legacyExtendSandboxStoreId && $environment == 'demo') {
                $storeIntegration->setExtendStoreUuid($legacyExtendSandboxStoreId);
            }
            $storeIntegration->setStoreId($storeId);
            $storeIntegration->setIntegrationId($integrationId);
            $this->storeIntegrationResource->save($storeIntegration);
            $this->generateUuidForStore($storeIntegration);
        }
    }

    /**
     * Generate a random UUID for Magento store
     *
     * @param StoreIntegrationInterface $storeIntegration
     * @return void
     * @throws AlreadyExistsException
     */
    public function generateUuidForStore(StoreIntegrationInterface $storeIntegration): void
    {

        if (!$storeIntegration->getStoreUuid()) {
            $uuid = $this->identityService->generateId();
            $storeIntegration->setStoreUuid($uuid);
        }

        $this->storeIntegrationResource->save($storeIntegration);
    }

    /**
     * Add Extend UUID to Magento store
     *
     * @param string $storeUuid
     * @param string $extendUuid
     * @return void
     * @throws NoSuchEntityException
     */
    public function addExtendUuidToStore(string $storeUuid, string $extendUuid): void
    {
        $storeIntegration = $this->getByUuid($storeUuid);

        if (is_null($storeIntegration->getId())) {
            throw new NoSuchEntityException();
        }
        $storeIntegration->setExtendStoreUuid($extendUuid);
        $this->storeIntegrationResource->save($storeIntegration);
    }

    /**
     * endpoint to save a client id and client secret to all store relationships under an integration
     *
     * @param string $consumerKey
     * @param string $clientId
     * @param string $clientSecret
     * @return void
     */
    public function attachClientIdAndSecretToIntegration(string $consumerKey, string $clientId, string $clientSecret): void
    {
        $consumer = $this->oauthService->loadConsumerByKey($consumerKey);

        $storeIntegrationCollection = $this->storeIntegrationCollectionFactory->create();
        $storeIntegrationCollection
            ->addFieldToFilter(\Extend\Integration\Api\Data\StoreIntegrationInterface::INTEGRATION_ID, $consumer->getId())
            ->load();

        foreach ($storeIntegrationCollection as $integration) {
            $integration->setClientId($clientId);
            $integration->setClientSecret(
                $this->encryptor->encrypt($clientSecret)
            );
            $this->storeIntegrationResource->save($integration);
        }
    }
}
