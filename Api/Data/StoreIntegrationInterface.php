<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api\Data;

interface StoreIntegrationInterface
{
    /**
     * Consts for integration stores table columns
     */
    const STORE_ID = 'store_id';
    const INTEGRATION_ID = 'integration_id';
    const STORE_UUID = 'store_uuid';
    const EXTEND_STORE_UUID = 'extend_store_uuid';
    const EXTEND_CLIENT_ID = 'client_id';
    const EXTEND_CLIENT_SECRET = 'client_secret';
    const DISABLED = 'disabled';
    const INTEGRATION_ERROR = 'integration_error';

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId): void;

    /**
     * Set integration ID
     *
     * @param int $integrationId
     * @return void
     */
    public function setIntegrationId(int $integrationId): void;

    /**
     * Set store UUID
     *
     * @param string $storeUuid
     * @return void
     */
    public function setStoreUuid(string $storeUuid): void;

    /**
     * Set Extend store UUID
     *
     * @param string $extendStoreUuid
     * @return void
     */
    public function setExtendStoreUuid(string $extendStoreUuid): void;

    /**
     * Set Extend client ID
     *
     * @deprecated [PAR-5480] Extend OAuth Client data is now handled by the ExtendOAuthClient
     * @see Extend/Integration/Api/Data/ExtendOAuthClientInterface - setExtendClientId
     * @param string $extendClientId
     * @return void
     */
    public function setExtendClientId(string $extendClientId): void;

    /**
     * Set Extend client secret
     *
     * @deprecated [PAR-5480] Extend OAuth Client data is now handled by the ExtendOAuthClient
     * @see Extend/Integration/Api/Data/ExtendOAuthClientInterface - setExtendClientSecret
     * @param string $extendClientSecret
     * @return void
     */
    public function setExtendClientSecret(string $extendClientSecret): void;

    /**
     * @param int $disabled
     * @return void
     */
    public function setDisabled(int $disabled): void;


    /**
     * 
     * Set the extend integration error
     * @param string $integrationError
     * @return void
     */
    public function setIntegrationError(?string $integrationError): void;

    /**
     * Get store ID
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Get integration ID
     *
     * @return int|null
     */
    public function getIntegrationId(): ?int;

    /**
     * @return string|null
     */
    public function getStoreUuid(): ?string;

    /**
     * @return string|null
     */
    public function getExtendStoreUuid(): ?string;

    /**
     * @deprecated [PAR-5480] Extend OAuth Client data is now handled by the ExtendOAuthClient
     * @see Extend/Integration/Api/Data/ExtendOAuthClientInterface - getExtendClientId
     * @return string|null
     */
    public function getExtendClientId(): ?string;

    /**
     * @deprecated [PAR-5480] Extend OAuth Client data is now handled by the ExtendOAuthClient
     * @see Extend/Integration/Api/Data/ExtendOAuthClientInterface - getExtendClientSecret
     * @return string|null
     */
    public function getExtendClientSecret(): ?string;

    /**
     * @return int|null
     */
    public function getDisabled(): ?int;

    public function getIntegrationError(): ?string;
}
