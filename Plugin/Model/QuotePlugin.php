<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Service\Extend;
use Extend\Integration\ViewModel\EnvironmentAndExtendStoreUuid;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

class QuotePlugin
{

    /**
     * @var Extend
     */
    private Extend $extend;

    /**
     * @var EnvironmentAndExtendStoreUuid
     */
    private EnvironmentAndExtendStoreUuid $environmentAndExtendStoreUuid;

    public function __construct(
        Extend $extend,
        EnvironmentAndExtendStoreUuid $environmentAndExtendStoreUuid
    ) {
        $this->extend = $extend;
        $this->environmentAndExtendStoreUuid = $environmentAndExtendStoreUuid;
    }

    /* ================================ plugin functions ================================ */

    /**
     * If a merchant product item was removed, remove the corresponding warranty
     * product item (if it exists)to keep qtys in sync.
     *
     * This should occur regardless of if the merchant has configured cart balancing.
     *
     * @param Quote $subject
     * @param int $itemId the id of the item being removed
     * @return void
     */
    public function beforeRemoveItem(Quote $subject, $itemId): void
    {
        if (!$this->extend->isEnabled()) {
            return;
        }

        $quoteItemBeingRemoved = $subject->getItemById($itemId);

        if (!Extend::isProductionProtectionSku($quoteItemBeingRemoved->getSku())) {
            // item being removed is a merchant product
            $correspondingWarrantyQuoteItems = $this
                ->getMatchingWarrantyProductQuoteItemsForMerchantProductQuoteItem($subject, $quoteItemBeingRemoved);
            if (count($correspondingWarrantyQuoteItems) !== 0) {
                foreach ($correspondingWarrantyQuoteItems as $warrantyItemToRemove) {
                    $subject->removeItem($warrantyItemToRemove->getId());
                }
                $subject->setTotalsCollectedFlag(false);
            }
        }

        return;
    }

    /**
     * Ensure a balanced cart with respect to Extend warranties.
     *
     * @param Quote $subject
     * @return void
     */
    public function beforeCollectTotals(Quote $subject): void
    {
        if (!$this->extend->isEnabled() || $subject->getTotalsCollectedFlag()) {
            return;
        }

        $quoteItems = $subject->getAllItems();

        foreach ($quoteItems as $quoteItem) {
            if (!Extend::isProductionProtectionSku($quoteItem->getSku())) {
                $correspondingWarrantyQuoteItems = $this
                    ->getMatchingWarrantyProductQuoteItemsForMerchantProductQuoteItem($subject, $quoteItem);
                if (!count($correspondingWarrantyQuoteItems)) {
                    continue;
                } elseif (count($correspondingWarrantyQuoteItems) === 1) {
                    $correspondingWarrantyQuoteItem = array_pop($correspondingWarrantyQuoteItems);
                    if ($correspondingWarrantyQuoteItem->getQty() > $quoteItem->getQty()) {
                        $correspondingWarrantyQuoteItem->setQty($quoteItem->getQty());
                        $subject->setTotalsCollectedFlag(false);
                    } elseif ($correspondingWarrantyQuoteItem->getQty() < $quoteItem->getQty() && $this->environmentAndExtendStoreUuid->isCartBalancingEnabled()) {
                        $correspondingWarrantyQuoteItem->setQty($quoteItem->getQty());
                        $subject->setTotalsCollectedFlag(false);
                    }
                    continue;
                } elseif (count($correspondingWarrantyQuoteItems) > 1) {
                    $totalWarrantiesInCart = array_reduce($correspondingWarrantyQuoteItems, function ($carry, $item) {
                        return $carry + $item->getQty();
                    }, 0);

                    if ($totalWarrantiesInCart === $quoteItem->getQty()) {
                        continue;
                    } elseif ($totalWarrantiesInCart < $quoteItem->getQty() && $this->environmentAndExtendStoreUuid->isCartBalancingEnabled()) {
                        $this->increaseQuantityForExistingWarrantyQuoteItems($subject, $correspondingWarrantyQuoteItems, $quoteItem->getQty());
                        continue;
                    } elseif ($totalWarrantiesInCart > $quoteItem->getQty()) {
                        $this->decreaseQuantityForExistingWarrantyQuoteItems($subject, $correspondingWarrantyQuoteItems, $quoteItem->getQty());
                        continue;
                    }
                }
            } elseif ($quoteItem->getOptionByCode('lead_token')) {
                $maxLeadQuantity = $quoteItem->getOptionByCode('lead_quantity')->getValue();
                if ($quoteItem->getQty() > $maxLeadQuantity) {
                    $quoteItem->setQty($maxLeadQuantity);
                    $subject->setTotalsCollectedFlag(false);
                } elseif ($quoteItem->getQty() < $maxLeadQuantity && $this->environmentAndExtendStoreUuid->isCartBalancingEnabled()) {
                    $quoteItem->setQty($maxLeadQuantity);
                    $subject->setTotalsCollectedFlag(false);
                }
            }
        }
        return;
    }

    /**
     * If the item being added is an Extend warranty, ensure the quantity being added is
     * privileged against any existing warranties in the quote that are for the same product.
     *
     * @param Quote $subject
     * @param Item $item the item being added to the quote
     * @return void
     */
    public function beforeAddItem(Quote $subject, Item $item): void
    {
        if (!$this->extend->isEnabled()) {
            return;
        }

        if (Extend::isProductionProtectionSku($item->getSku()) && $item->getOptionByCode('associated_product_sku')) {
            $correspondingMerchantProductSku = $item->getOptionByCode('associated_product_sku')->getValue();
            $correspondingMerchantQuoteItem = null;

            foreach ($subject->getAllItems() as $potentialMatchingMerchantQuoteItem) {
                if ($potentialMatchingMerchantQuoteItem->getSku() === $correspondingMerchantProductSku) {
                    $correspondingMerchantQuoteItem = $potentialMatchingMerchantQuoteItem;
                    break;
                }
            }

            if (!$correspondingMerchantQuoteItem) {
                return;
            }

            $existingExtendProductsForMerchantQuoteItem = $this
                ->getMatchingWarrantyProductQuoteItemsForMerchantProductQuoteItem($subject, $correspondingMerchantQuoteItem);

            if (!count($existingExtendProductsForMerchantQuoteItem)) {
                return;
            }

            $targetQtyForExistingWarranties = $correspondingMerchantQuoteItem->getQty() - $item->getQty();

            $this->decreaseQuantityForExistingWarrantyQuoteItems($subject, $existingExtendProductsForMerchantQuoteItem, $targetQtyForExistingWarranties);
        }
        return;
    }

    /* ================================ helper functions ================================ */

    /**
     * Balance a collection of warranty items to match the target quantity by adding the
     * target quantity to the most expensive existing warranty quote item.
     *
     * @param Quote $quote
     * @param array $warrantyQuoteItems
     * @param int $targetQty
     * @return void
     */
    private function increaseQuantityForExistingWarrantyQuoteItems(
        Quote $quote,
        array $warrantyQuoteItems,
        int $targetQty
    ): void {
        // ordered warranties from most expensive to least expensive
        usort($warrantyQuoteItems, function ($a, $b) {
            return $b->getCustomPrice() <=> $a->getCustomPrice();
        });
        $quantityOfWarrantiesAlreadyInTheCart = array_reduce($warrantyQuoteItems, function ($carry, $item) {
            return $carry + $item->getQty();
        }, 0);
        $qtyToAdd = $targetQty - $quantityOfWarrantiesAlreadyInTheCart;
        $mostExpensiveWarrantyQuoteItem = $warrantyQuoteItems[0];
        $mostExpensiveWarrantyQuoteItem->setQty($mostExpensiveWarrantyQuoteItem->getQty() + $qtyToAdd);
        $quote->setTotalsCollectedFlag(false);
    }

    /**
     * Balance a collection of warranty items to match the target quantity by decrementing
     * the quantity of the least expensive warranty quote item until the target quantity
     * is reached. If the quantity of the least expensive warranty quote item hits zero,
     * it is removed from the quote and the next least expensive warranty quote item is
     * decremented until the target quantity is reached.
     *
     * @param Quote $quote
     * @param array $warrantyQuoteItems
     * @param int $targetQty
     */
    private function decreaseQuantityForExistingWarrantyQuoteItems(
        Quote $quote,
        array $warrantyQuoteItems,
        int $targetQty
    ): void {
        // sort least expensive to most expensive
        usort($warrantyQuoteItems, function ($a, $b) {
            return $a->getCustomPrice() <=> $b->getCustomPrice();
        });
        $quantityOfWarrantiesAlreadyInTheCart = array_reduce($warrantyQuoteItems, function ($carry, $item) {
            return $carry + $item->getQty();
        }, 0);
        $quantityOfWarrantiesToRemove = $quantityOfWarrantiesAlreadyInTheCart - $targetQty;
        while ($quantityOfWarrantiesToRemove > 0) {
            $cheapestWarrantyQuoteItem = $warrantyQuoteItems[0];
            if ($cheapestWarrantyQuoteItem->getQty() - $quantityOfWarrantiesToRemove <= 0) {
                $quantityOfWarrantiesToRemove -= $cheapestWarrantyQuoteItem->getQty();
                $quote->removeItem($cheapestWarrantyQuoteItem->getId());
                $quote->setTotalsCollectedFlag(false);
                array_shift($warrantyQuoteItems);
            } else {
                $cheapestWarrantyQuoteItem->setQty($cheapestWarrantyQuoteItem->getQty() - $quantityOfWarrantiesToRemove);
                $quote->setTotalsCollectedFlag(false);
                $quantityOfWarrantiesToRemove = 0;
            }
        }
    }

    /**
     * Find the warranty quote item that corresponds to the merchant product quote item, if any.
     *
     * @param Item $merchantProductQuoteItem
     * @param array $presentQuoteItems
     * @return array $matchingWarrantyQuoteItems
     */
    private function getMatchingWarrantyProductQuoteItemsForMerchantProductQuoteItem(
        Quote $quote,
        Item $merchantProductQuoteItem
    ): array {
        $presentQuoteItems = $quote->getAllItems();
        $matchingWarrantyQuoteItems = [];
        foreach ($presentQuoteItems as $potentialMatchingWarrantyQuoteItem) {
            if ($potentialMatchingWarrantyQuoteItem->getOptionByCode('associated_product_sku') &&
                !$potentialMatchingWarrantyQuoteItem->getOptionByCode('lead_token') &&
                $merchantProductQuoteItem->getSku() === $potentialMatchingWarrantyQuoteItem->getOptionByCode('associated_product_sku')->getValue()
            ) {
                $matchingWarrantyQuoteItems[] = $potentialMatchingWarrantyQuoteItem;
            }
        }
        return $matchingWarrantyQuoteItems;
    }
}
