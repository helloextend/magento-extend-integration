<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Extend\Integration\Api\Data\ShippingProtectionInterface;
use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Model\ShippingProtectionFactory;
use Extend\Integration\Model\ResourceModel\ShippingProtectionTotal as ShippingProtectionTotalResource;
use Extend\Integration\Model\ResourceModel\ShippingProtectionTotal\CollectionFactory as ShippingProtectionTotalCollectionFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;

class ShippingProtectionTotalRepository implements
    \Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface
{
    /**
     * @var ShippingProtectionTotalFactory
     */
    private ShippingProtectionTotalFactory $shippingProtectionTotalFactory;

    /**
     * @var ShippingProtectionTotalResource
     */
    private ShippingProtectionTotalResource $shippingProtectionTotalResource;

    /**
     * @var ShippingProtectionTotalCollectionFactory
     */
    private ShippingProtectionTotalCollectionFactory $shippingProtectionTotalCollection;

    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var ShippingProtectionFactory
     */
    private ShippingProtectionFactory $shippingProtectionFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @param ShippingProtectionFactory $shippingProtectionFactory
     * @param ShippingProtectionTotalFactory $shippingProtectionTotalFactory
     * @param ShippingProtectionTotalResource $shippingProtectionTotalResource
     * @param ShippingProtectionTotalCollectionFactory $shippingProtectionTotalCollection
     * @param Session $checkoutSession
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ShippingProtectionFactory $shippingProtectionFactory,
        ShippingProtectionTotalFactory $shippingProtectionTotalFactory,
        ShippingProtectionTotalResource $shippingProtectionTotalResource,
        ShippingProtectionTotalCollectionFactory $shippingProtectionTotalCollection,
        Session $checkoutSession,
        SerializerInterface $serializer
    ) {
        $this->shippingProtectionTotalFactory = $shippingProtectionTotalFactory;
        $this->shippingProtectionTotalResource = $shippingProtectionTotalResource;
        $this->shippingProtectionTotalCollection = $shippingProtectionTotalCollection;
        $this->checkoutSession = $checkoutSession;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
        $this->serializer = $serializer;
    }

    /**
     * Get session cache key for an entity
     *
     * @param int $entityId
     * @param int $entityTypeId
     * @return string
     */
    private function getSessionCacheKey(int $entityId, int $entityTypeId): string
    {
        return 'shipping_protection_' . $entityId . '_' . $entityTypeId;
    }

    /**
     * Get Shipping Protection total record by entity ID and entity type
     *
     * @param int|null $entityId
     * @param int $entityTypeId
     * @return ShippingProtectionTotal
     */
    public function get(?int $entityId, int $entityTypeId): ShippingProtectionTotal
    {
        // Only use session cache if entityId is valid
        // During payment capture flows, entityId might be null before order is persisted
        if ($entityId !== null && is_int($entityId)) {
            $sessionCacheKey = $this->getSessionCacheKey($entityId, $entityTypeId);

            // Check if the result is in the session cache
            if ($this->checkoutSession->hasData($sessionCacheKey)) {
                $serializedData = $this->checkoutSession->getData($sessionCacheKey);
                $totalData = $this->serializer->unserialize($serializedData);

                if ($totalData && !empty($totalData)) {
                    $total = $this->shippingProtectionTotalFactory->create();
                    $total->setData($totalData);
                    return $total;
                }
            }
        }

        // Fetch from database
        $collection = $this->shippingProtectionTotalCollection->create();

        // Only filter by entity_id if it's not null
        if ($entityId !== null) {
            $collection->addFieldToFilter('entity_id', $entityId);
        }

        $firstItem = $collection
            ->addFieldToFilter('entity_type_id', $entityTypeId)
            ->load()
            ->getFirstItem();

        // Only cache if we have valid data and valid entityId
        if ($firstItem && $firstItem->getId() && $entityId !== null && is_int($entityId)) {
            $sessionCacheKey = $this->getSessionCacheKey($entityId, $entityTypeId);
            // Cache in session for persistence across requests
            $this->checkoutSession->setData(
                $sessionCacheKey,
                $this->serializer->serialize($firstItem->getData())
            );
        }

        return $firstItem;
    }

    /**
     * Get Shipping Protection total by record ID
     *
     * @param int $shippingProtectionTotalId
     * @return ShippingProtectionTotal
     */
    public function getById(int $shippingProtectionTotalId): ShippingProtectionTotal
    {
        $shippingProtectionTotal = $this->shippingProtectionTotalFactory->create();
        $this->shippingProtectionTotalResource->load(
            $shippingProtectionTotal,
            $shippingProtectionTotalId
        );

        return $shippingProtectionTotal;
    }

    /**
     * Save Shipping Protection total
     *
     * @param int $entityId
     * @param int $entityTypeId
     * @param string $spQuoteId
     * @param float $price
     * @param string $currency
     * @param float|null $basePrice
     * @param string|null $baseCurrency
     * @param float|null $spTaxAmt
     * @param string|null $offerType
     * @return ShippingProtectionTotal
     * @throws AlreadyExistsException
     */
    public function save(
        int $entityId,
        int $entityTypeId,
        string $spQuoteId,
        float $price,
        string $currency,
        ?float $basePrice,
        ?string $baseCurrency,
        ?float $spTaxAmt,
        ?string $offerType
    ): ShippingProtectionTotal {
        //need to make $entityId and $entityTypeId optional for SDK ajax call
        if (!($shippingProtectionTotal = $this->get($entityId, $entityTypeId))) {
            $shippingProtectionTotal = $this->shippingProtectionTotalFactory->create();
        }

        $shippingProtectionTotal->setEntityId($entityId);
        $shippingProtectionTotal->setEntityTypeId($entityTypeId);
        $shippingProtectionTotal->setSpQuoteId($spQuoteId);
        $shippingProtectionTotal->setShippingProtectionBasePrice($basePrice ?: $price);
        $shippingProtectionTotal->setShippingProtectionBaseCurrency($baseCurrency ?: $currency);
        $shippingProtectionTotal->setShippingProtectionPrice($price);
        $shippingProtectionTotal->setShippingProtectionCurrency($currency);
        $shippingProtectionTotal->setShippingProtectionTax($spTaxAmt);
        $shippingProtectionTotal->setOfferType($offerType);

        $this->shippingProtectionTotalResource->save($shippingProtectionTotal);

        // Update session cache
        $sessionCacheKey = $this->getSessionCacheKey($entityId, $entityTypeId);
        $this->checkoutSession->setData(
            $sessionCacheKey,
            $this->serializer->serialize($shippingProtectionTotal->getData())
        );

        return $shippingProtectionTotal;
    }

    /**
     * Save Shipping Protection total using Magento quote ID in the session
     *
     * @param string $spQuoteId
     * @param float $price
     * @param string $currency
     * @param float|null $basePrice
     * @param string|null $baseCurrency
     * @param float|null $spTax
     * @param string|null $offerType
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function saveBySdk(
        string $spQuoteId,
        float $price,
        string $currency,
        float $basePrice = null,
        string $baseCurrency = null,
        float $spTax = null,
        string $offerType = null
    ): void {
        if ($offerType === 'SAFE_PACKAGE') {
          $price = 0.0;
          $basePrice = 0.0;
        }

        $entityId = $this->checkoutSession->getQuote()->getId();
        $this->save(
            $entityId,
            ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID,
            $spQuoteId,
            $price / 100,
            $currency,
            $basePrice / 100 ?: null,
            $baseCurrency ?: null,
            $spTax ?: null,
            $offerType ?: null
        );
    }

    /**
     * Delete Shipping Protection total by record ID
     *
     * @param int $shippingProtectionTotalId
     * @return void
     * @throws \Exception
     */
    public function deleteById(int $shippingProtectionTotalId)
    {
        $shippingProtectionTotal = $this->getById($shippingProtectionTotalId);
        $this->shippingProtectionTotalResource->delete($shippingProtectionTotal);

        // Clear cache for this entity using its entity ID and type directly
        $entityId = $shippingProtectionTotal->getEntityId();
        $entityTypeId = $shippingProtectionTotal->getEntityTypeId();
        if ($entityId && $entityTypeId) {
            // Clear session cache
            $sessionCacheKey = $this->getSessionCacheKey($entityId, $entityTypeId);
            $this->checkoutSession->unsetData($sessionCacheKey);
        }
    }

    /**
     * @return void
     */
    public function delete(): void
    {
        $entityId = $this->checkoutSession->getQuote()->getId();
        $entityTypeId = ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID;
        $shippingProtection = $this->get(
            $entityId,
            $entityTypeId
        );
        $this->shippingProtectionTotalResource->delete($shippingProtection);

        // Clear session cache
        $sessionCacheKey = $this->getSessionCacheKey($entityId, $entityTypeId);
        $this->checkoutSession->unsetData($sessionCacheKey);
    }

    /**
     * Get Shipping Protection Quote Record and Saturate Shipping Protection Extension Attributes -
     * supports Quote, Order, Invoice, and Credit Memo entities
     *
     * @param int $entityId
     * @param int $entityTypeId
     * @param ExtensibleDataInterface $result
     * @return void
     */
    public function getAndSaturateExtensionAttributes(
        int $entityId,
        int $entityTypeId,
        ExtensibleDataInterface $result
    ): void {
        $shippingProtectionTotal = $this->get($entityId, $entityTypeId);

        if (!$shippingProtectionTotal->getData() ||
            sizeof($shippingProtectionTotal->getData()) === 0
        ) {
            return;
        }

        $extensionAttributes = $result->getExtensionAttributes();
        $shippingProtection = $this->shippingProtectionFactory->create();

        $shippingProtection->setBase($shippingProtectionTotal->getShippingProtectionBasePrice());
        $shippingProtection->setBaseCurrency(
            $shippingProtectionTotal->getShippingProtectionBaseCurrency()
        );
        $shippingProtection->setPrice($shippingProtectionTotal->getShippingProtectionPrice());
        $shippingProtection->setCurrency($shippingProtectionTotal->getShippingProtectionCurrency());
        $shippingProtection->setSpQuoteId($shippingProtectionTotal->getSpQuoteId());
        $shippingProtection->setShippingProtectionTax($shippingProtectionTotal->getShippingProtectionTax());
        $shippingProtection->setOfferType($shippingProtectionTotal->getOfferType());

        $extensionAttributes->setShippingProtection($shippingProtection);
        $result->setExtensionAttributes($extensionAttributes);
    }

    /**
     * Save Shipping Protection extension attribute to Shipping Protection table and
     * resaturate Shipping Protection Extension Attributes -
     * supports Quote, Order, Invoice, and Credit Memo entities
     *
     * @param ShippingProtectionInterface $shippingProtectionExtensionAttribute
     * @param ExtensibleDataInterface $result
     * @param int $entityTypeId
     * @return void
     * @throws AlreadyExistsException
     */
    public function saveAndResaturateExtensionAttribute(
        ShippingProtectionInterface $shippingProtectionExtensionAttribute,
        ExtensibleDataInterface $result,
        int $entityTypeId
    ): void {
        if ($shippingProtectionExtensionAttribute->getBase() >= 0 &&
            $shippingProtectionExtensionAttribute->getBaseCurrency() &&
            $shippingProtectionExtensionAttribute->getPrice() >= 0 &&
            $shippingProtectionExtensionAttribute->getCurrency() &&
            $shippingProtectionExtensionAttribute->getSpQuoteId()
        ) {
            if ($result->getEntityId()) {
                $this->save(
                    $result->getEntityId(),
                    $entityTypeId,
                    $shippingProtectionExtensionAttribute->getSpQuoteId(),
                    $shippingProtectionExtensionAttribute->getPrice(),
                    $shippingProtectionExtensionAttribute->getCurrency(),
                    $shippingProtectionExtensionAttribute->getBase(),
                    $shippingProtectionExtensionAttribute->getBaseCurrency(),
                    $shippingProtectionExtensionAttribute->getShippingProtectionTax(),
                    $shippingProtectionExtensionAttribute->getOfferType(),
                );

                $shippingProtection = $this->shippingProtectionFactory->create();

                $shippingProtection->setBase($shippingProtectionExtensionAttribute->getBase());
                $shippingProtection->setBaseCurrency(
                    $shippingProtectionExtensionAttribute->getBaseCurrency()
                );
                $shippingProtection->setPrice($shippingProtectionExtensionAttribute->getPrice());
                $shippingProtection->setCurrency(
                    $shippingProtectionExtensionAttribute->getCurrency()
                );
                $shippingProtection->setSpQuoteId(
                    $shippingProtectionExtensionAttribute->getSpQuoteId()
                );

                $shippingProtection->setShippingProtectionTax(
                    $shippingProtectionExtensionAttribute->getShippingProtectionTax()
                );

                $shippingProtection->setOfferType(
                    $shippingProtectionExtensionAttribute->getOfferType()
                );

                $extensionAttributesForResaturation = $result->getExtensionAttributes();
                $extensionAttributesForResaturation->setShippingProtection($shippingProtection);
                $result->setExtensionAttributes($extensionAttributesForResaturation);
            }
        }
    }

    /**
     * Save Shipping Protection total using Magento quote ID and cart ID as provided via API
     *
     * @param string $cartId
     * @param string $spQuoteId
     * @param float $price
     * @param string $currency
     * @param float|null $basePrice
     * @param string|null $baseCurrency
     * @param float|null $spTax
     * @param string|null $offerType
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function saveByApi(
        string $cartId,
        string $spQuoteId,
        float $price,
        string $currency,
        ?float $basePrice = null,
        ?string $baseCurrency = null,
        ?float $spTax = null,
        ?string $offerType = null
    ): void {
        $this->save(
            $cartId,
            ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID,
            $spQuoteId,
            $price / 100,
            $currency,
            $basePrice / 100 ?: null,
            $baseCurrency ?: null,
            $spTax ?: null,
            $offerType ?: null
        );
    }
}
