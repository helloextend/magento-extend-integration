<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Extend\Integration\Api\Data\ExtendOAuthClientInterface;
use Magento\Framework\Model\AbstractModel;

class ExtendOAuthClient extends AbstractModel implements ExtendOAuthClientInterface
{
    /**
     * ExtendOAuthClient constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Extend\Integration\Model\ResourceModel\ExtendOAuthClient::class);
    }

    /**
     * Set integration ID
     *
     * @param int $integrationId
     * @return void
     */
    public function setIntegrationId(int $integrationId): void
    {
        $this->setData(self::INTEGRATION_ID, $integrationId);
    }

    /**
     * Set Extend client ID
     *
     * @param string $extendClientId
     * @return void
     */
    public function setExtendClientId(string $extendClientId): void
    {
        $this->setData(self::EXTEND_CLIENT_ID, $extendClientId);
    }

    /**
     * Set Extend client secret
     *
     * @param string $extendClientSecret
     * @return void
     */
    public function setExtendClientSecret(string $extendClientSecret): void
    {
        $this->setData(self::EXTEND_CLIENT_SECRET, $extendClientSecret);
    }

    /**
     * Get Integration ID
     *
     * @return int|null
     */
    public function getIntegrationId(): ?int
    {
        return $this->getData(self::INTEGRATION_ID);
    }

    /**
     * Get Extend client ID
     *
     * @return string|null
     */
    public function getExtendClientId(): ?string
    {
        return $this->getData(self::EXTEND_CLIENT_ID);
    }

    /**
     * Get Extend client secret
     *
     * @return string|null
     */
    public function getExtendClientSecret(): ?string
    {
        return $this->getData(self::EXTEND_CLIENT_SECRET);
    }
}
