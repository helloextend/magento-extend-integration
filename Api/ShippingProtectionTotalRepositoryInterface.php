<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api;

use Extend\Integration\Api\Data\ShippingProtectionInterface;
use Extend\Integration\Model\ShippingProtectionTotal;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ShippingProtectionTotalRepositoryInterface
{
    /**
     * Get Shipping Protection total record by entity ID and entity type
     *
     * @param int $entityId
     * @param int $entityTypeId
     * @return ShippingProtectionTotal
     */
    public function get(int $entityId, int $entityTypeId): ShippingProtectionTotal;

    /**
     * Get Shipping Protection total by record ID
     *
     * @param int $shippingProtectionTotalId
     * @return ShippingProtectionTotal
     */
    public function getById(int $shippingProtectionTotalId): ShippingProtectionTotal;

    /**
     * Save Shipping Protection total
     *
     * @param int $entityId
     * @param int $entityTypeId
     * @param string $spQuoteId
     * @param float $price
     * @param string $currency
     * @param float|null $basePrice
     * @return ShippingProtectionTotal
     */
    public function save(int $entityId, int $entityTypeId, string $spQuoteId, float $price, string $currency, ?float $basePrice, ?string $baseCurrency): ShippingProtectionTotal;

    /**
     * Save Shipping Protection total using Magento quote ID in the session
     *
     * @param string $spQuoteId
     * @param float $price
     * @param string $currency
     * @param float|null $basePrice
     * @param string|null $baseCurrency
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function saveBySdk(string $spQuoteId, float $price, string $currency, float $basePrice = null, string $baseCurrency = null): void;

    /**
     * Delete Shipping Protection total by record ID
     *
     * @param int $shippingProtectionTotalId
     * @return void
     */
    public function deleteById(int $shippingProtectionTotalId);

    /**
     * @return void
     */
    public function delete(): void;

    /**
     * Get Shipping Protection Quote Record and Saturate Shipping Protection Extension Attributes -
     * supports Quote, Order, Invoice, and Credit Memo entities
     *
     * @param int $entityId
     * @param int $entityTypeId
     * @param ExtensibleDataInterface $result
     * @return void
     */
    public function getAndSaturateExtensionAttributes(int $entityId, int $entityTypeId, ExtensibleDataInterface $result): void;

    /**
     * Save Shipping Protection extension attribute to Shipping Protection table and
     * resaturate Shipping Protection Extension Attributes -
     * supports Quote, Order, Invoice, and Credit Memo entities
     *
     * @param ShippingProtectionInterface $shippingProtectionExtensionAttribute
     * @param ExtensibleDataInterface $result
     * @param int $entityTypeId
     * @return void
     */
    public function saveAndResaturateExtensionAttribute(ShippingProtectionInterface $shippingProtectionExtensionAttribute, \Magento\Framework\Api\ExtensibleDataInterface $result, int $entityTypeId): void;

    /**
     * Save Shipping Protection total using Magento quote ID and cart ID as provided via API
     *
     * @param string $cartId
     * @param string $spQuoteId
     * @param float $price
     * @param string $currency
     * @param float|null $basePrice
     * @param string|null $baseCurrency
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function saveByApi(string $cartId, string $spQuoteId, float $price, string $currency, float $basePrice = null, string $baseCurrency = null): void;
}
