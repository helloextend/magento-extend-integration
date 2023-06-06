<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

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

    public function __construct(
        OrderItemExtensionFactory $orderItemExtensionFactory,
        CollectionFactory $quoteItemCollectionFactory
    ) {
        $this->orderItemExtensionFactory = $orderItemExtensionFactory;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
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

                // for each of the product's configured options, set the corresponding extension attribute
                // according to the quote item's corresponding option value.
                foreach ($productOptions as $o) {
                    $optionId = $o->getId();
                    if ($existingOption = $quoteItem->getOptionByCode("option_$optionId")) {
                        $optionTitle = $o->getTitle();
                        switch ($optionTitle) {
                            case 'Plan ID':
                                $extensionAttributes->setPlanId($existingOption->getValue());
                                break;
                            case 'Plan Type':
                                $extensionAttributes->setPlanType($existingOption->getValue());
                                break;
                            case 'Associated Product':
                                $extensionAttributes->setAssociatedProduct(
                                    $existingOption->getValue()
                                );
                                break;
                            case 'Term':
                                $extensionAttributes->setTerm($existingOption->getValue());
                                break;
                            case 'Order Offer Plan Id':
                                $extensionAttributes->setOfferPlanId($existingOption->getValue());
                                break;
                        }
                    }
                }

                $item->setExtensionAttributes($extensionAttributes);
            }
        }

        return $searchResult;
    }
}
