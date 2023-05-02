<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Extend\Integration\Service\Extend;
use Extend\Integration\Api\ProductProtectionInterface;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;

class ProductProtection implements ProductProtectionInterface
{
    /**
     * @var Cart
     */
    private Cart $cart;

    /**
     * @var ProductFactory
     */
    private ProductFactory $productFactory;

    /**
     * @return void
     */
    public function __construct(Cart $cart, ProductFactory $productFactory)
    {
        $this->cart = $cart;
        $this->productFactory = $productFactory;
    }

    /**
     * Add product protection to cart
     *
     * @param int $quantity
     * @param string $productId
     * @param string $planId
     * @param int $price
     * @param int $term
     * @param string $coverageType
     * @param string $token = null
     * @param float $listPrice = null
     * @param string $orderOfferPlanId = null
     * @return void
     */
    public function add(
        int $quantity,
        string $productId,
        string $planId,
        int $price,
        int $term,
        string $coverageType,
        string $leadToken = null,
        float $listPrice = null,
        string $orderOfferPlanId = null
    ): void {
        $product = $this->productFactory->create();
        $product->load($product->getIdBySku(Extend::WARRANTY_PRODUCT_SKU));

        $params = [];
        $options = [];
        $params['qty'] = $quantity;
        $params['product'] = $product->getId();
        $product->setData('extend_plan_price', $price / 100);

        foreach ($product->getOptions() as $o) {
            $optionId = $o->getId();
            switch ($o->getTitle()) {
                case 'Associated Product':
                    $options[$optionId] = $productId;
                    break;
                case 'Plan Type':
                    $options[$optionId] = $coverageType;
                    break;
                case 'Plan ID':
                    $options[$optionId] = $planId;
                    break;
                case 'Term':
                    $options[$optionId] = $term;
                    break;
                case 'List Price':
                    if (isset($listPrice)) {
                        $options[$optionId] = $listPrice;
                    }
                    break;
                case 'Order Offer Plan Id':
                    if (isset($orderOfferPlanId)) {
                        $options[$optionId] = $orderOfferPlanId;
                    }
                    break;
                case 'Lead Token':
                    if (isset($leadToken)) {
                        $options[$optionId] = $leadToken;
                    }
                    break;
                default:
                    break;
            }
        }
        $params['options'] = $options;
        $this->cart->addProduct($product, $params);
        $this->cart->save();
    }
}
