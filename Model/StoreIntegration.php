<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

class StoreIntegration extends \Magento\Framework\Model\AbstractModel implements \Extend\Integration\Api\Data\StoreIntegrationInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Extend\Integration\Model\ResourceModel\StoreIntegration');
    }

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId): void
    {
        $this->setData(self::STORE_ID, $storeId);
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
     * Set store UUID
     *
     * @param string $storeUuid
     * @return void
     */
    public function setStoreUuid(string $storeUuid): void
    {
        $this->setData(self::STORE_UUID, $storeUuid);
    }

    /**
     * Set Extend store UUID
     *
     * @param string $extendStoreUuid
     * @return void
     */
    public function setExtendStoreUuid(string $extendStoreUuid): void
    {
        $this->setData(self::EXTEND_STORE_UUID, $extendStoreUuid);
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
     * @param int $disabled
     * @return void
     */
    public function setDisabled(int $disabled): void
    {
        $this->setData(self::DISABLED, $disabled);
    }

    /**
     * Get store ID
     *
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        return $this->getData(self::STORE_ID);
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
     * @return string|null
     */
    public function getStoreUuid(): ?string
    {
        return $this->getData(self::STORE_UUID);
    }

    /**
     * @return string|null
     */
    public function getExtendStoreUuid(): ?string
    {
        return $this->getData(self::EXTEND_STORE_UUID);
    }

    /**
     * @return string|null
     */
    public function getExtendClientId(): ?string
    {
        return $this->getData(self::EXTEND_CLIENT_ID);
    }

    /**
     * @return string|null
     */
    public function getExtendClientSecret(): ?string
    {
        return $this->getData(self::EXTEND_CLIENT_SECRET);
    }

    /**
     * @return int|null
     */
    public function getDisabled(): ?int
    {
        return $this->getData(self::DISABLED);
    }
}
