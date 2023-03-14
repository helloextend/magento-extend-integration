<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

class ShippingProtectionTotal extends \Magento\Framework\Model\AbstractModel implements \Extend\Integration\Api\Data\ShippingProtectionTotalInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Extend\Integration\Model\ResourceModel\ShippingProtectionTotal');
    }

    /**
     * Set entity ID
     *
     * @param self::QUOTE_ENTITY_TYPE_ID|self::ORDER_ENTITY_TYPE_ID|self::INVOICE_ENTITY_TYPE_ID|self::CREDITMEMO_ENTITY_TYPE_ID $entityId
     * @return void
     */
    public function setEntityId($entityId)
    {
        $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Set entity type ID
     *
     * @param int $entityTypeId
     * @return void
     */
    public function setEntityTypeId(int $entityTypeId)
    {
        $this->setData(self::ENTITY_TYPE_ID, $entityTypeId);
    }

    /**
     * Set SP Quote ID
     *
     * @param string $spQuoteId
     * @return void
     */
    public function setSpQuoteId(string $spQuoteId)
    {
        $this->setData(self::SP_QUOTE_ID, $spQuoteId);
    }

    /**
     * Set Shipping Protection base price
     *
     * @param float $shippingProtectionBasePrice
     * @return void
     */
    public function setShippingProtectionBasePrice(float $shippingProtectionBasePrice)
    {
        $this->setData(self::SHIPPING_PROTECTION_BASE_PRICE, $shippingProtectionBasePrice);
    }

    /**
     * Set Shipping Protection base currency
     *
     * @param string $shippingProtectionBaseCurrency
     * @return void
     */
    public function setShippingProtectionBaseCurrency(string $shippingProtectionBaseCurrency)
    {
        $this->setData(self::SHIPPING_PROTECTION_BASE_CURRENCY, $shippingProtectionBaseCurrency);
    }

    /**
     * Set Shipping Protection price
     *
     * @param float $shippingProtectionPrice
     * @return void
     */
    public function setShippingProtectionPrice(float $shippingProtectionPrice)
    {
        $this->setData(self::SHIPPING_PROTECTION_PRICE, $shippingProtectionPrice);
    }

    /**
     * Set Shipping Protection currency
     *
     * @param string $shippingProtectionCurrency
     * @return void
     */
    public function setShippingProtectionCurrency(string $shippingProtectionCurrency)
    {
        $this->setData(self::SHIPPING_PROTECTION_CURRENCY, $shippingProtectionCurrency);
    }

    /**
     * Get entity ID
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Get entity type ID
     *
     * @return int
     */
    public function getEntityTypeId(): int
    {
        return $this->getData(self::ENTITY_TYPE_ID);
    }

    /**
     * Get SP Quote ID
     *
     * @return string
     */
    public function getSpQuoteId(): string
    {
        return $this->getData(self::SP_QUOTE_ID);
    }

    /**
     * Get Shipping Protection base price
     *
     * @return float
     */
    public function getShippingProtectionBasePrice(): float
    {
        return $this->getData(self::SHIPPING_PROTECTION_BASE_PRICE);
    }

    /**
     * Get Shipping Protection base currency
     *
     * @return string
     */
    public function getShippingProtectionBaseCurrency(): string
    {
        return $this->getData(self::SHIPPING_PROTECTION_BASE_CURRENCY);
    }

    /**
     * Get Shipping Protection price
     *
     * @return float
     */
    public function getShippingProtectionPrice(): float
    {
        return $this->getData(self::SHIPPING_PROTECTION_PRICE);
    }

    /**
     * Get Shipping Protection currency
     *
     * @return string
     */
    public function getShippingProtectionCurrency(): string
    {
        return $this->getData(self::SHIPPING_PROTECTION_CURRENCY);
    }
}
