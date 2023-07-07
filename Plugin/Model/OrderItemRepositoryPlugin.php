<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Model\ProductProtection;
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
                    foreach (ProductProtection::CUSTOM_OPTION_CODES as $optionCode) {
                        $existingOption = $quoteItem->getOptionByCode($optionCode);
                        if ($existingOption) {
                            $optionValue = $existingOption->getValue();
                            switch ($optionCode) {
                                case ProductProtection::PLAN_ID_CODE:
                                    $productProtection->setPlanId($optionValue);
                                    break;
                                case ProductProtection::PLAN_TYPE_CODE:
                                    $productProtection->setPlanType($optionValue);
                                    break;
                                case ProductProtection::ASSOCIATED_PRODUCT_SKU_CODE:
                                    $productProtection->setAssociatedProduct($optionValue);
                                    break;
                                case ProductProtection::TERM_CODE:
                                    $productProtection->setTerm($optionValue);
                                    break;
                                case ProductProtection::OFFER_PLAN_ID_CODE:
                                    $productProtection->setOfferPlanId($optionValue);
                                    break;
                                case ProductProtection::LIST_PRICE_CODE:
                                    $productProtection->setListPrice($optionValue);
                                    break;
                                case ProductProtection::LEAD_TOKEN_CODE:
                                    $productProtection->setLeadtoken($optionValue);
                                    break;
                                case ProductProtection::LEAD_QUANTITY_CODE:
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
