<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api;

use Extend\Integration\Api\Data\ExtendOAuthClientInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface ExtendOAuthClientRepositoryInterface
{
    /**
     * Get by Integration ID
     *
     * @param int $integrationId
     * @return ExtendOAuthClientInterface
     * @throws NoSuchEntityException
     */
    public function getByIntegrationId(int $integrationId): ExtendOAuthClientInterface;

    /**
     * Get all Extend OAuth Clients
     *
     * @return array
     */
    public function getList(): array;

    /**
     * Add or update Extend OAuth Client
     *
     * @param string $consumerKey
     * @param string $clientId
     * @param string $clientSecret
     * @return void
     * @throws NoSuchEntityException
     */
    public function saveClientToIntegration(string $consumerKey, string $clientId, string $clientSecret): void;
}
