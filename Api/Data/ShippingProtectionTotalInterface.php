<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api\Data;

interface ShippingProtectionTotalInterface
{
    /**
     * Consts for Shipping Protection table columns
     */
    const SHIPPING_PROTECTION_BASE_PRICE = 'shipping_protection_base_price';
    const SHIPPING_PROTECTION_BASE_CURRENCY = 'shipping_protection_base_currency';
    const SHIPPING_PROTECTION_PRICE = 'shipping_protection_price';
    const SHIPPING_PROTECTION_CURRENCY = 'shipping_protection_currency';
    const ENTITY_ID = 'entity_id';
    const ENTITY_TYPE_ID = 'entity_type_id';
    const SP_QUOTE_ID = 'sp_quote_id';

    /**
     * Consts for Shipping Protection database table entity type, per row
     */
    const QUOTE_ENTITY_TYPE_ID = 4;
    const ORDER_ENTITY_TYPE_ID = 5;
    const INVOICE_ENTITY_TYPE_ID = 6;
    const CREDITMEMO_ENTITY_TYPE_ID = 7;

    /**
     * Set entity ID
     *
     * @param self::QUOTE_ENTITY_TYPE_ID|self::ORDER_ENTITY_TYPE_ID|self::INVOICE_ENTITY_TYPE_ID|self::CREDITMEMO_ENTITY_TYPE_ID $entityId
     * @return void
     */
    public function setEntityId($entityId);

    /**
     * Set entity type ID
     *
     * @param int $entityTypeId
     * @return void
     */
    public function setEntityTypeId(int $entityTypeId);

    /**
     * Set SP Quote ID
     *
     * @param string $spQuoteId
     * @return void
     */
    public function setSpQuoteId(string $spQuoteId);

    /**
     * Set Shipping Protection base price
     *
     * @param float $shippingProtectionBasePrice
     * @return void
     */
    public function setShippingProtectionBasePrice(float $shippingProtectionBasePrice);

    /**
     * Set Shipping Protection base currency
     *
     * @param string $shippingProtectionBaseCurrency
     * @return void
     */
    public function setShippingProtectionBaseCurrency(string $shippingProtectionBaseCurrency);

    /**
     * Set Shipping Protection price
     *
     * @param float $shippingProtectionPrice
     * @return void
     */
    public function setShippingProtectionPrice(float $shippingProtectionPrice);

    /**
     * Set Shipping Protection currency
     *
     * @param string $shippingProtectionCurrency
     * @return void
     */
    public function setShippingProtectionCurrency(string $shippingProtectionCurrency);

    /**
     * Get entity ID
     *
     * @return int
     */
    public function getEntityId(): int;

    /**
     * Get entity type ID
     *
     * @return int
     */
    public function getEntityTypeId(): int;

    /**
     * Get SP Quote ID
     *
     * @return string
     */
    public function getSpQuoteId(): string;

    /**
     * Get Shipping Protection base price
     *
     * @return float
     */
    public function getShippingProtectionBasePrice(): float;

    /**
     * Get Shipping Protection base currency
     *
     * @return string
     */
    public function getShippingProtectionBaseCurrency(): string;

    /**
     * Get Shipping Protection price
     *
     * @return float
     */
    public function getShippingProtectionPrice(): float;

    /**
     * Get Shipping Protection currency
     *
     * @return string
     */
    public function getShippingProtectionCurrency(): string;
}
