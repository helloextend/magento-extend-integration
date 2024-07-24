<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api\Data;

interface ExtendOAuthClientInterface
{
    /**
     * Consts for table columns
     */
    public const INTEGRATION_ID = 'integration_id';
    public const EXTEND_CLIENT_ID = 'client_id';
    public const EXTEND_CLIENT_SECRET = 'client_secret';
    public const EXTEND_ACCESS_TOKEN = 'access_token';

    /**
     * Set integration ID
     *
     * @param int $integrationId
     * @return void
     */
    public function setIntegrationId(int $integrationId): void;

    /**
     * Set Extend OAuth Client ID
     *
     * @param string $extendClientId
     * @return void
     */
    public function setExtendClientId(string $extendClientId): void;

    /**
     * Set Extend OAuth Client Secret
     *
     * @param string $extendClientSecret
     * @return void
     */
    public function setExtendClientSecret(string $extendClientSecret): void;

    /**
     * Set Extend Access Token
     *
     * @param string $extendAccessToken
     * @return void
     */
    public function setExtendAccessToken(string $extendAccessToken): void;

    /**
     * Get integration ID
     *
     * @return int|null
     */
    public function getIntegrationId(): ?int;

    /**
     * Get Extend OAuth Client ID
     *
     * @return string|null
     */
    public function getExtendClientId(): ?string;

    /**
     * Get Extend OAuth Client Secret
     *
     * @return string|null
     */
    public function getExtendClientSecret(): ?string;

    /**
     * Get Extend Access Token
     *
     * @return string|null
     */
    public function getExtendAccessToken(): ?string;
}
