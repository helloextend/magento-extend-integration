<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api;

use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

interface StoreIntegrationRepositoryInterface
{
    /**
     * Get store UUIDs with store code
     *
     * @param string $code
     * @return StoreIntegrationInterface
     * @throws NoSuchEntityException
     */
    public function getByStoreCodeAndActiveEnvironment(string $code): StoreIntegrationInterface;

    /**
     * Get store UUIDs with store id
     *
     * @param int $storeId
     * @return StoreIntegrationInterface
     */
    public function getByStoreIdAndActiveEnvironment(int $storeId): StoreIntegrationInterface;

    /**
     * Get store UUIDs with store id and active environment (integration)
     *
     * @param int $storeId
     * @param int $integrationId
     * @return StoreIntegrationInterface
     */
    public function getByStoreIdAndIntegrationId(int $storeId, int $integrationId): StoreIntegrationInterface;

    /**
     * Get store Extend UUID and store ID by Magento store UUID
     *
     * @param string $storeUuid
     * @return StoreIntegrationInterface
     */
    public function getByUuid(string $storeUuid): StoreIntegrationInterface;

    /**
     * Get store Magento UUID and store ID by Extend store UUID
     *
     * @param string $extendStoreUuid
     * @return StoreIntegrationInterface
     */
    public function getByExtendUuid(string $extendStoreUuid): StoreIntegrationInterface;

    /**
     * Get stores associated with Integration (Extend Integration(s) only)
     *
     * @param int $integrationId
     * @return array
     */
    public function getListByIntegration(int $integrationId): array;

    /**
     * Get stores associated with Integration by Consumer Key (Extend Integration(s) only)
     *
     * @param string $consumerKey
     * @return array|StoreIntegrationInterface
     */
    public function getListByConsumerKey(string $consumerKey): array;

    /**
     * Link store to integration
     *
     * @param int $integrationId
     * @param int $storeId
     * @return void
     * @throws AlreadyExistsException
     */
    public function saveStoreToIntegration(int $integrationId, int $storeId): void;

    /**
     * Generate a random UUID for Magento store
     *
     * @param StoreIntegrationInterface $storeIntegration
     * @return void
     * @throws AlreadyExistsException
     */
    public function generateUuidForStore(StoreIntegrationInterface $storeIntegration): void;

    /**
     * Add Extend UUID to Magento store
     *
     * @param string $storeUuid
     * @param string $extendUuid
     * @return void
     * @throws NoSuchEntityException
     */
    public function addExtendUuidToStore(string $storeUuid, string $extendUuid): void;

    /**
     * endpoint to save a client id and client secret to all store relationships under an integration
     *
     * @param string $consumerKey
     * @param string $clientId
     * @param string $clientSecret
     * @return void
     */
    public function attachClientIdAndSecretToIntegration(string $consumerKey, string $clientId, string $clientSecret): void;
}
