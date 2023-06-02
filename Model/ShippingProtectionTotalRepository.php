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
    private Session $checkoutSession;
    private ShippingProtectionFactory $shippingProtectionFactory;

    /**
     * @param ShippingProtectionTotalFactory $shippingProtectionTotalFactory
     * @param ShippingProtectionTotalResource $shippingProtectionTotalResource
     * @param ShippingProtectionTotalCollectionFactory $shippingProtectionTotalCollection
     */
    public function __construct(
        ShippingProtectionFactory $shippingProtectionFactory,
        ShippingProtectionTotalFactory $shippingProtectionTotalFactory,
        ShippingProtectionTotalResource $shippingProtectionTotalResource,
        ShippingProtectionTotalCollectionFactory $shippingProtectionTotalCollection,
        Session $checkoutSession
    ) {
        $this->shippingProtectionTotalFactory = $shippingProtectionTotalFactory;
        $this->shippingProtectionTotalResource = $shippingProtectionTotalResource;
        $this->shippingProtectionTotalCollection = $shippingProtectionTotalCollection;
        $this->checkoutSession = $checkoutSession;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
    }

    /**
     * Get Shipping Protection total record by entity ID and entity type
     *
     * @param int $entityId
     * @param int $entityTypeId
     * @return ShippingProtectionTotal
     */
    public function get($entityId, $entityTypeId): ShippingProtectionTotal
    {
        $collection = $this->shippingProtectionTotalCollection->create();
        $firstItem = $collection
            ->addFieldToFilter('entity_id', $entityId)
            ->addFieldToFilter('entity_type_id', $entityTypeId)
            ->load()
            ->getFirstItem();

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
        ?string $baseCurrency
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

        $this->shippingProtectionTotalResource->save($shippingProtectionTotal);

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
        string $baseCurrency = null
    ): void {
        $entityId = $this->checkoutSession->getQuote()->getId();
        $this->save(
            $entityId,
            ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID,
            $spQuoteId,
            $price / 100,
            $currency,
            $basePrice / 100 ?: null,
            $baseCurrency ?: null
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
    }

    /**
     * @return void
     */
    public function delete(): void
    {
        $entityId = $this->checkoutSession->getQuote()->getId();
        $shippingProtection = $this->get(
            $entityId,
            ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID
        );
        $this->shippingProtectionTotalResource->delete($shippingProtection);
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

        if (
            !$shippingProtectionTotal->getData() ||
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
        if (
            $shippingProtectionExtensionAttribute->getBase() &&
            $shippingProtectionExtensionAttribute->getBaseCurrency() &&
            $shippingProtectionExtensionAttribute->getPrice() &&
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
                    $shippingProtectionExtensionAttribute->getBaseCurrency()
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
        float $basePrice = null,
        string $baseCurrency = null
    ): void {
        $this->save(
            $cartId,
            ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID,
            $spQuoteId,
            $price / 100,
            $currency,
            $basePrice / 100 ?: null,
            $baseCurrency ?: null
        );
    }
}
