<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api\Data;

interface ShippingProtectionInterface
{
    /**
     * Consts for Shipping Protection
     */
    const BASE = 'base';
    const BASE_CURRENCY = 'base_currency';
    const PRICE = 'price';
    const CURRENCY = 'currency';
    const SP_QUOTE_ID = 'sp_quote_id';
    const SHIPPING_PROTECTION_TAX= 'shipping_protection_tax';

    /**
     * Set base price
     *
     * @param float $base
     * @return void
     */
    public function setBase(float $base);

    /**
     * Set base currency
     *
     * @param string $baseCurrency
     * @return void
     */
    public function setBaseCurrency(string $baseCurrency);

    /**
     * Set price
     *
     * @param float $price
     * @return void
     */
    public function setPrice(float $price);

    /**
     * Set currency
     *
     * @param string $currency
     * @return void
     */
    public function setCurrency(string $currency);

    /**
     * Set SP Quote ID
     *
     * @param string $spQuoteId
     * @return void
     */
    public function setSpQuoteId(string $spQuoteId);

    /**
     * Set SP Tax Amount
     *
     * @param float $spTax
     * @return void
     */
    public function setShippingProtectionTax(float $spTax);

    /**
     * Get base price
     *
     * @return float
     */
    public function getBase(): float;

    /**
     * Get base currency
     *
     * @return string
     */
    public function getBaseCurrency(): string;

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Get SP Quote ID
     *
     * @return string
     */
    public function getSpQuoteId(): string;

    /**
     * Get SP Tax Amount
     *
     * @return float | null
     */
    public function getShippingProtectionTax(): ?float;
}
