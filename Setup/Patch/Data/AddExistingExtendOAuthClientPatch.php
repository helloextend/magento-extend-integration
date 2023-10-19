<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Patch\Data;

use Exception;
use Extend\Integration\Model\ResourceModel\StoreIntegration\CollectionFactory as StoreIntegrationCollectionFactory;
use Extend\Integration\Model\ResourceModel\ExtendOAuthClient as ExtendOAuthClientResource;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Extend\Integration\Model\ExtendOAuthClientFactory;
use Extend\Integration\Api\Data\ExtendOAuthClientInterface;

/**
 * This patch will migrate any existing Extend OAuth Client data from the extend_store_integration
 * table to the extend_integration_oauth_client.
 */
class AddExistingExtendOAuthClientPatch implements DataPatchInterface
{
    /** @var StoreIntegrationCollectionFactory */
    private $storeIntegrationCollectionFactory;

    /** @var ExtendOAuthClientResource */
    private $extendOAuthClientResource;

    /** @var ExtendOAuthClientFactory */
    private $extendOAuthClientFactory;

    /**
     * AddExistingExtendOAuthClientPatch constructor
     *
     * @param StoreIntegrationCollectionFactory $storeIntegrationCollectionFactory
     * @param ExtendOAuthClientResource $extendOAuthClientResource
     * @param ExtendOAuthClientFactory $extendOAuthClientFactory
     */
    public function __construct(
        StoreIntegrationCollectionFactory $storeIntegrationCollectionFactory,
        ExtendOAuthClientResource $extendOAuthClientResource,
        ExtendOAuthClientFactory $extendOAuthClientFactory
    ) {
        $this->storeIntegrationCollectionFactory = $storeIntegrationCollectionFactory;
        $this->extendOAuthClientResource = $extendOAuthClientResource;
        $this->extendOAuthClientFactory = $extendOAuthClientFactory;
    }

  /**
   * @inheritDoc
   */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     *
     * @throws SetupException
     */
    public function apply()
    {
        try {
            $storeIntegrationCollection = $this->storeIntegrationCollectionFactory->create();

            // Filter out any records that do not have a client_id or client_secret
            $storeIntegrationCollection->addFieldToFilter('client_id', ['notnull' => true])
                ->addFieldToFilter('client_secret', ['notnull' => true])
                ->getSelect()
                ->group('integration_id');

            $storeIntegrations = $storeIntegrationCollection->getItems();

            foreach ($storeIntegrations as $storeIntegration) {
              // Load the Extend OAuth Client if it already exists
                $extendOAuthClient = $this->extendOAuthClientFactory->create();
                $this->extendOAuthClientResource->load(
                    $extendOAuthClient,
                    $storeIntegration->getIntegrationId(),
                    ExtendOAuthClientInterface::INTEGRATION_ID
                );

              // If it doesn't exist, create a new one by setting the integrationId
                if (!$extendOAuthClient->getId()) {
                    $extendOAuthClient->setIntegrationId($storeIntegration->getIntegrationId());
                }

              // Set the client_id and client_secret (this will update the record if it already exists)
                $extendOAuthClient->setExtendClientId($storeIntegration->getClientId());
                $extendOAuthClient->setExtendClientSecret($storeIntegration->getClientSecret());

              // Save the Extend OAuth Client
                $this->extendOAuthClientResource->save($extendOAuthClient);
            }
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase(
                    'There was a problem applying the Extend Integration OAuth Client Patch: %1',
                    [$exception->getMessage()]
                )
            );
        }
    }
}
