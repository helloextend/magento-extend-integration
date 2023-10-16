<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Extend\Integration\Api\Data\ExtendOAuthClientInterface;
use Extend\Integration\Api\ExtendOAuthClientRepositoryInterface;
use Extend\Integration\Model\ResourceModel\ExtendOAuthClient as ExtendOAuthClientResource;
use Extend\Integration\Model\ResourceModel\ExtendOAuthClient\Collection;
use Extend\Integration\Model\ResourceModel\ExtendOAuthClient\CollectionFactory;
use Extend\Integration\Model\ExtendOAuthClientFactory;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ExtendOAuthClientRepository implements ExtendOAuthClientRepositoryInterface
{
    /** @var ExtendOAuthClientResource */
    private $extendOAuthClientResource;

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var ExtendOAuthClientFactory */
    private $extendOAuthClientFactory;

    /** @var IntegrationServiceInterface */
    private $integrationService;

    /** @var OauthServiceInterface */
    private $oauthService;

    /** @var EncryptorInterface */
    private $encryptor;

    /**
     * ExtendOAuthClientRepository constructor
     *
     * @param ExtendOAuthClientResource $extendOAuthClientResource
     * @param CollectionFactory $collectionFactory
     * @param ExtendOAuthClientFactory $extendOAuthClientFactory
     * @param IntegrationServiceInterface $integrationService
     * @param OauthServiceInterface $oauthService
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ExtendOAuthClientResource $extendOAuthClientResource,
        CollectionFactory $collectionFactory,
        ExtendOAuthClientFactory $extendOAuthClientFactory,
        IntegrationServiceInterface $integrationService,
        OauthServiceInterface $oauthService,
        EncryptorInterface $encryptor
    ) {
        $this->extendOAuthClientResource = $extendOAuthClientResource;
        $this->collectionFactory = $collectionFactory;
        $this->extendOAuthClientFactory = $extendOAuthClientFactory;
        $this->integrationService = $integrationService;
        $this->oauthService = $oauthService;
        $this->encryptor = $encryptor;
    }

    /**
     * Get entity by Integration ID
     *
     * @param int $integrationId
     * @return ExtendOAuthClientInterface
     * @throws NoSuchEntityException
     */
    public function getByIntegrationId(int $integrationId): ExtendOAuthClientInterface
    {
        $extendOAuthClient = $this->extendOAuthClientFactory->create();
        $this->extendOAuthClientResource->load(
            $extendOAuthClient,
            $integrationId,
            ExtendOAuthClientInterface::INTEGRATION_ID
        );

        if (!$extendOAuthClient->getId()) {
            throw new NoSuchEntityException(
                __(
                    'Extend OAuth Client with integration ID %1 does not exist.',
                    $integrationId
                )
            );
        }

        return $extendOAuthClient;
    }

    /**
     * Get all Extend OAuth Clients
     *
     * @var Collection $collection
     * @return array
     */
    public function getList(): array
    {
        $collection = $this->collectionFactory->create();

        return $collection->getItems();
    }

    /**
     * Add or update Extend OAuth Client
     *
     * @param string $consumerKey
     * @param string $clientId
     * @param string $clientSecret
     * @return void
     * @throws NoSuchEntityException
     */
    public function saveClientToIntegration(string $consumerKey, string $clientId, string $clientSecret): void
    {
        // Get the Magento OAuth Consumer Id
        $consumer = $this->oauthService->loadConsumerByKey($consumerKey);
        $consumerId = $consumer->getId();
        if (!$consumerId) {
            throw new NoSuchEntityException(
                __(
                    'Consumer with consumer key %1 does not exist.',
                    $consumerKey
                )
            );
        }

        // Get the Magento Integration Id
        $integration = $this->integrationService->findByConsumerId($consumerId);
        $integrationId = $integration->getId();
        if (!$integrationId) {
            throw new NoSuchEntityException(
                __(
                    'Integration with consumer key %1 does not exist.',
                    $consumerKey
                )
            );
        }

        // Load the Extend OAuth Client if it already exists
        $extendOAuthClient = $this->extendOAuthClientFactory->create();
        $this->extendOAuthClientResource->load(
            $extendOAuthClient,
            $integrationId,
            ExtendOAuthClientInterface::INTEGRATION_ID
        );

        // If it doesn't exist, create a new one by setting the integrationId
        if (!$extendOAuthClient->getId()) {
            $extendOAuthClient->setIntegrationId($integrationId);
        }

        // Set the client_id and client_secret (this will update the record if it already exists)
        $extendOAuthClient->setExtendClientId($clientId);
        $extendOAuthClient->setExtendClientSecret(
            $this->encryptor->encrypt($clientSecret)
        );

        // Save the Extend OAuth Client
        $this->extendOAuthClientResource->save($extendOAuthClient);
    }
}
