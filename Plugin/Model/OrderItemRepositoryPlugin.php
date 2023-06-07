<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Model\ProductProtectionFactory;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;

class OrderItemRepositoryPlugin
{
    /**
     * @var OrderItemExtensionFactory
     */
    private OrderItemExtensionFactory $orderItemExtensionFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $quoteItemCollectionFactory;

    /**
     * @var ProductProtectionFactory
     */
    private ProductProtectionFactory $productProtectionFactory;

    public function __construct(
        OrderItemExtensionFactory $orderItemExtensionFactory,
        CollectionFactory $quoteItemCollectionFactory,
        ProductProtectionFactory $productProtectionFactory
    ) {
        $this->orderItemExtensionFactory = $orderItemExtensionFactory;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->productProtectionFactory = $productProtectionFactory;
    }

    /**
     * This plugin injects product protection product data into the order's product protection order items
     *
     * @param OrderItemRepositoryInterface $subject
     * @param OrderItemSearchResultInterface $searchResult
     * @return OrderItemSearchResultInterface
     */
    public function afterGetList(
        OrderItemRepositoryInterface $subject,
        OrderItemSearchResultInterface $searchResult
    ): OrderItemSearchResultInterface {
        $orderItems = $searchResult->getItems();

        // if order items exist and are an array
        if (isset($orderItems) && is_array($orderItems)) {
            $orderItems = $searchResult->getItems();

            foreach ($orderItems as &$item) {
                if ($item->getSku() === 'extend-protection-plan') {
                    // create extension attributes
                    $extensionAttributes = $this->orderItemExtensionFactory->create();

                    // get the relevant quote item
                    $quoteItemId = $item->getQuoteItemId();
                    $quoteItemCollection = $this->quoteItemCollectionFactory->create();
                    $quoteItem = $quoteItemCollection
                        ->addFieldToSelect('*')
                        ->addFieldToFilter('item_id', $quoteItemId)
                        ->getFirstItem();

                    // get the quote item's product's options
                    $productOptions = $quoteItem->getProduct()->getOptions();

                    $productProtection = $this->productProtectionFactory->create();

                    // for each of the product's configured options, set the corresponding extension attribute
                    // according to the quote item's corresponding option value.
                    foreach ($productOptions as $o) {
                        $optionId = $o->getId();
                        if ($existingOption = $quoteItem->getOptionByCode("option_$optionId")) {
                            $optionTitle = $o->getTitle();
                            $optionValue = $existingOption->getValue();
                            switch ($optionTitle) {
                                case 'Plan ID':
                                    $productProtection->setPlanId($optionValue);
                                    break;
                                case 'Plan Type':
                                    $productProtection->setPlanType($optionValue);
                                    break;
                                case 'Associated Product':
                                    $productProtection->setAssociatedProduct($optionValue);
                                    break;
                                case 'Term':
                                    $productProtection->setTerm($optionValue);
                                    break;
                                case 'Order Offer Plan Id':
                                    $productProtection->setOfferPlanId($optionValue);
                                    break;
                                case 'List Price':
                                    $productProtection->setListPrice($optionValue);
                                    break;
                                case 'Lead Token':
                                    $productProtection->setLeadtoken($optionValue);
                                    break;
                                case 'Lead Quantity':
                                    $productProtection->setLeadQuantity($optionValue);
                                    break;
                            }
                        }
                    }

                    $extensionAttributes->setProductProtection($productProtection);

                    // set the extension attributes to the item
                    $item->setExtensionAttributes($extensionAttributes);
                }
            }
        }

        // return the search result
        return $searchResult;
    }
}
