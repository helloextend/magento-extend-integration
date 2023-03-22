<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\Data\ShippingProtectionInterface;

class ShippingProtection extends \Magento\Framework\Model\AbstractModel implements ShippingProtectionInterface
{
    /**
     * Set base price
     *
     * @param float $basePrice
     * @return void
     */
    public function setBase(float $base)
    {
        $this->setData(self::BASE, $base);
    }

    /**
     * Set base currency
     *
     * @param string $baseCurrency
     * @return void
     */
    public function setBaseCurrency(string $baseCurrency)
    {
        $this->setData(self::BASE_CURRENCY, $baseCurrency);
    }

    /**
     * Set price
     *
     * @param float $price
     * @return void
     */
    public function setPrice(float $price)
    {
        $this->setData(self::PRICE, $price);
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return void
     */
    public function setCurrency(string $currency)
    {
        $this->setData(self::CURRENCY, $currency);
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
     * Get base price
     *
     * @return float
     */
    public function getBase(): float
    {
        return $this->getData(self::BASE);
    }

    /**
     * Get base currency
     *
     * @return string
     */
    public function getBaseCurrency(): string
    {
        return $this->getData(self::BASE_CURRENCY);
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->getData(self::PRICE);
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->getData(self::CURRENCY);
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
}
