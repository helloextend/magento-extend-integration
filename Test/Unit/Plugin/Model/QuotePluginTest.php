<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Extend\Integration\Plugin\Model\QuotePlugin;
use Extend\Integration\Service\Extend;
use Extend\Integration\ViewModel\EnvironmentAndExtendStoreUuid;

class QuotePluginTest extends TestCase
{

    /**
     * @var Extend | MockObject
     */
    private $extendMock;

    /**
     * @var EnvironmentAndExtendStoreUuid | MockObject
     */
    private $envViewModelMock;

    /**
     * @var QuotePlugin
     */
    private $testSubject;

    /**
     * @var Quote | MockObject
     */
    private $quoteMock;

    protected function setUp(): void
    {
        // create mock constructor args for the tested class
        $this->extendMock = $this->createStub(Extend::class);
        $this->envViewModelMock = $this->createStub(EnvironmentAndExtendStoreUuid::class);
        // create the class to test
        $this->testSubject = new QuotePlugin($this->extendMock, $this->envViewModelMock);
        // create arguments for tested method(s)
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setTotalsCollectedFlag'])
            ->onlyMethods(['getAllVisibleItems', 'getItemById', 'removeItem'])
            ->getMock();
        $this->quoteMock->method('setTotalsCollectedFlag')->willReturn($this->quoteMock);
        $this->quoteMock->method('removeItem')->willReturn($this->quoteMock);
    }

  /* ======================================== beforeRemoveItem ======================================== */

    // extend is not enabled -> do nothing
    public function testBeforeRemoveItemExtendIsNotEnabled()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => false,
            'existing_quote_items' => [$merchantQuoteItem],
            'quote_item_being_removed' => $merchantQuoteItem
        ]);
        $this->expectRemoveItemToBeCalledTimes(0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeRemoveItem($this->quoteMock, $merchantQuoteItem->getId());
    }

    // extend is enabled and cart contains only merchant products -> do nothing
    public function testBeforeRemoveItemQuoteContainsOnlyMerchantProducts()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => true,
            'existing_quote_items' => [$merchantQuoteItem],
            'quote_item_being_removed' => $merchantQuoteItem
        ]);
        $this->expectRemoveItemToBeCalledTimes(0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeRemoveItem($this->quoteMock, $merchantQuoteItem->getId());
    }

    // extend is enabled and cart contains merchant products and corresponding warranty products;
    // extend product removed -> do nothing
    public function testBeforeRemoveItemQuoteContainsWarrantyProductWhichIsBeingRemoved()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $warrantyQuoteItem = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => true,
            'existing_quote_items' => [$merchantQuoteItem, $warrantyQuoteItem],
            'quote_item_being_removed' => $warrantyQuoteItem
        ]);
        $this->expectRemoveItemToBeCalledTimes(0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeRemoveItem($this->quoteMock, $warrantyQuoteItem->getId());
    }

    // extend is enabled and cart contains merchant products and corresponding warranty products;
    // merchant product removed -> remove warranties
    public function testBeforeRemoveItemQuoteContainsWarrantyProductForMerchantProductBeingRemoved()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $warrantyQuoteItem = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => true,
            'existing_quote_items' => [$merchantQuoteItem, $warrantyQuoteItem],
            'quote_item_being_removed' => $merchantQuoteItem
        ]);
        $actualArgs = [];
        $this->expectRemoveItemToBeCalledTimes(1, $actualArgs);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeRemoveItem($this->quoteMock, $merchantQuoteItem->getId());
        $this->assertEquals([$warrantyQuoteItem->getId()], $actualArgs);
    }

    /// extend is enabled and cart contains merchant products and multiple different corresponding warranty products;
    // merchant product removed -> remove all warranties
    public function testBeforeRemoveItemQuoteContainsMultipleWarrantyProductsForMerchantProductBeingRemoved()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(2, 'merchant_product_A');
        $warrantyQuoteItem1yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $warrantyQuoteItem2yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $this->setTestConditions([
            'extend_enabled' => true,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem1yr,
                $warrantyQuoteItem2yr
            ],
            'quote_item_being_removed' => $merchantQuoteItem
        ]);
        $actualArgs = [];
        $this->expectRemoveItemToBeCalledTimes(2, $actualArgs);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeRemoveItem($this->quoteMock, $merchantQuoteItem->getId());
        $this->assertEqualsCanonicalizing([$warrantyQuoteItem1yr->getId(), $warrantyQuoteItem2yr->getId()], $actualArgs);
    }

    // extend is enabled and cart contains 2 different merchant products and corresponding warranty products for each merchant product;
    // one of the merchant products is removed -> remove only the corresponding warranties
    public function testBeforeRemoveItemQuoteContainsMultipleWarrantyProductsForMultipleMerchantProductsAndMerchantProductIsRemoved()
    {
        $merchantQuoteItemA = $this->createQuoteItemMock(2, 'merchant_product_A');
        $warrantyQuoteItemA1yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $warrantyQuoteItemA2yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $merchantQuoteItemB = $this->createQuoteItemMock(2, 'merchant_product_B');
        $warrantyQuoteItemB1yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_B', 699);
        $warrantyQuoteItemB2yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_B', 1099);
        $this->setTestConditions([
            'extend_enabled' => true,
            'existing_quote_items' => [
                $merchantQuoteItemA,
                $warrantyQuoteItemA1yr,
                $warrantyQuoteItemA2yr,
                $merchantQuoteItemB,
                $warrantyQuoteItemB1yr,
                $warrantyQuoteItemB2yr

            ],
            'quote_item_being_removed' => $merchantQuoteItemA
        ]);
        $actualArgs = [];
        $this->expectRemoveItemToBeCalledTimes(2, $actualArgs);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeRemoveItem($this->quoteMock, $merchantQuoteItemA->getId());
        $this->assertEqualsCanonicalizing([$warrantyQuoteItemA1yr->getId(), $warrantyQuoteItemA2yr->getId()], $actualArgs);
    }

  /* ======================================= beforeCollectTotals ======================================= */

    // extend is not enabled -> do nothing
    public function testBeforeCollectTotalsExtendIsNotEnabled()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $warrantyQuoteItem = $this->createQuoteItemMock(3, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => false,
            'cart_balancing_enabled' => false,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem
            ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend is enabled and cart balancing is not enabled -> decrease count of warranty quote items to 1
    // regardless of cart balancing config
    public function testBeforeCollectTotalsCartBalancingIsNotEnabled()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $warrantyQuoteItem = $this->createQuoteItemMock(3, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => false,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem
            ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 1, 1);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains only merchant products -> do nothing
    public function testBeforeCollectTotalsCartOnlyContainsMerchantProducts()
    {
        $merchantQuoteItems = [
            $this->createQuoteItemMock(1, 'merchant_product_A'),
            $this->createQuoteItemMock(1, 'merchant_product_B'),
            $this->createQuoteItemMock(1, 'merchant_product_C')
        ];
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => true,
            'existing_quote_items' => $merchantQuoteItems
        ]);
        foreach ($merchantQuoteItems as $merchantQuoteItem) {
            $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        }
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 1x merchant product and
    // 1x corresponding warranty product -> do nothing
    public function testBeforeCollectTotalsCartContains1MerchantProductAnd1WarrantyProduct()
    {
        $quoteItems = [
            $this->createQuoteItemMock(1, 'merchant_product_A'),
            $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A')
        ];
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => true,
            'existing_quote_items' => $quoteItems
        ]);
        foreach ($quoteItems as $quoteItem) {
            $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($quoteItem, 0);
        }
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 1x merchant product and
    // 3x corresponding warranty product -> decrease warranty product qty 2x
    public function testBeforeCollectTotalsCartContains1MerchantProductAnd3WarrantyProduct()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $warrantyQuoteItem = $this->createQuoteItemMock(3, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => true,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem
            ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 1, 1);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 3x merchant product and
    // 1x corresponding warranty product -> increase warranty product qty 2x
    public function testBeforeCollectTotalsCartContains3MerchantProductAnd1WarrantyProduct()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(3, 'merchant_product_A');
        $warrantyQuoteItem = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => true,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem
            ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 1, 3);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend is enabled; cart balancing is NOT enabled, and cart contains 3x merchant product and
    // 1x corresponding warranty product -> do not increase warranty qty because cart balancing is OFF
    public function testBeforeCollectTotalsCartBalancingIsOffCartContains3MerchantProductAnd1WarrantyProduct()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(3, 'merchant_product_A');
        $warrantyQuoteItem = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A');
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => false,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem
            ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 3x merchant product and
    // 1x corresponding warranty product (cheaper) and 2x corresponding warranty product (more expensive) -> do nothing
    public function testBeforeCollectTotalsCartContainsBalancedWarrantiesWithDifferentLengthsForOneProduct()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(3, 'merchant_product_A');
        $warrantyQuoteItem1yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $warrantyQuoteItem2yr = $this->createQuoteItemMock(2, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => true,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem1yr,
                $warrantyQuoteItem2yr
            ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem1yr, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem2yr, 0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 3x merchant product and
    // 1x corresponding warranty product (cheaper) and 1x corresponding warranty product (more expensive)
    // -> increase more expensive warranty product qty 1x
    public function testBeforeCollectTotalsCartContainsNotEnoughWarrantiesWithDifferentLengthsForOneProduct()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(3, 'merchant_product_A');
        $warrantyQuoteItem1yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $warrantyQuoteItem2yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => true,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem1yr,
                $warrantyQuoteItem2yr
            ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem1yr, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem2yr, 1, 2);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 3x merchant product and
    // 2x corresponding warranty product (cheaper) and 2x corresponding warranty product (more expensive)
    // -> decrease cheaper warranty product qty 1x
    public function testBeforeCollectTotalsCartContainsTooManyWarrantiesWithDifferentLengthsForOneProduct()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(3, 'merchant_product_A');
        $warrantyQuoteItem1yr = $this->createQuoteItemMock(2, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $warrantyQuoteItem2yr = $this->createQuoteItemMock(2, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $this->setTestConditions([
            'extend_enabled' => true,
            'cart_balancing_enabled' => true,
            'existing_quote_items' => [
                $merchantQuoteItem,
                $warrantyQuoteItem1yr,
                $warrantyQuoteItem2yr
            ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem1yr, 1, 1);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem2yr, 0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 3x warranty item for 1 merchant product not in the cart;
    // -> remove all warranties
    public function testBeforeCollectTotalsCartContainsOnlyWarrantiesNoLeadToken()
    {
    }

    // extend + cart balancing are enabled and cart contains 3x warranty item for 1 merchant product not in the cart;
    // the warranty item has a lead token and max lead qty of 2x -> decrease the warranty item qty 1x
    public function testBeforeCollectTotalsCartContainsOnlyWarrantiesWithLeadTokenWithQuantityGreaterThanMaxLeadQty()
    {
        $warrantyQuoteItem = $this->createQuoteItemMock(3, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 899, 'some_lead_token', 2);
        $this->setTestConditions([
          'extend_enabled' => true,
          'cart_balancing_enabled' => true,
          'existing_quote_items' => [
            $warrantyQuoteItem
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 1, 2);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 3x warranty item for 1 merchant product not in the cart;
    // the warranty item has a lead token and lead qty of 3x -> do nothing
    public function testBeforeCollectTotalsCartContainsOnlyWarrantiesWithLeadTokenWithQtyEqualToMaxLeadQty()
    {
        $warrantyQuoteItem = $this->createQuoteItemMock(3, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 899, 'some_lead_token', 3);
        $this->setTestConditions([
          'extend_enabled' => true,
          'cart_balancing_enabled' => true,
          'existing_quote_items' => [
            $warrantyQuoteItem
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 3x warranty item for 1 merchant product not in the cart;
    // the warranty item has a lead token and max lead qty of 4x -> do nothing
    public function testBeforeCollectTotalsCartContainsOnlyWarrantiesWithLeadTokenWithQuantityLessThanMaxLeadQty()
    {
        $warrantyQuoteItem = $this->createQuoteItemMock(3, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 899, 'some_lead_token', 4);
        $this->setTestConditions([
          'extend_enabled' => true,
          'cart_balancing_enabled' => true,
          'existing_quote_items' => [
            $warrantyQuoteItem
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 1, 4);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 2x 1yr warranty items, one of which has a lead token (so
    // they exist as separate items), for 2x corresponding merchant product in the cart;
    // -> increase the warranty quote item quantity 1x because the lead should not be considered in relation
    // to the merchant product in the cart: it should be considered as a standalone.
    public function testBeforeCollectTotalsMultipleWarrantyItemsOneWithLeadTokenOneWithoutHavingQuantityTooLowForCorrespondingMerchantProduct()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(2, 'merchant_product_A');
        $warrantyQuoteItem1yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $warrantyQuoteItem1yrWithLeadToken = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599, 'some_lead_token', 1);
        $this->setTestConditions([
          'extend_enabled' => true,
          'cart_balancing_enabled' => true,
          'existing_quote_items' => [
            $merchantQuoteItem,
            $warrantyQuoteItem1yr,
            $warrantyQuoteItem1yrWithLeadToken
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem1yr, 1, 2);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem1yrWithLeadToken, 0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

    // extend + cart balancing are enabled and cart contains 4x 1yr warranty items, one of which has a lead token (so
    // they exist as separate items) with qty 1, the other having qty 3, for 2x corresponding merchant product in the cart;
    // -> decrease the warranty quote item quantity 1x because the lead should not be considered in relation
    // to the merchant product in the cart: it should be considered as a standalone.
    public function testBeforeCollectTotalsMultipleWarrantyItemsOneWithLeadTokenOneWithoutHavingQuantityTooHighForCorrespondingMerchantProduct()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(2, 'merchant_product_A');
        $warrantyQuoteItem1yr = $this->createQuoteItemMock(3, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $warrantyQuoteItem1yrWithLeadToken = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599, 'some_lead_token', 1);
        $this->setTestConditions([
          'extend_enabled' => true,
          'cart_balancing_enabled' => true,
          'existing_quote_items' => [
            $merchantQuoteItem,
            $warrantyQuoteItem1yr,
            $warrantyQuoteItem1yrWithLeadToken
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem1yr, 1, 2);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem1yrWithLeadToken, 0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeCollectTotals($this->quoteMock);
    }

  /* ========================================== beforeAddItem ========================================== */

    // extend is not enabled -> do nothing
    public function testBeforeAddItemExtendNotEnabled()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(5, 'merchant_product_A');
        $existingWarrantyQuoteItem1yr = $this->createQuoteItemMock(5, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $newWarrantyQuoteItem2yr = $this->createQuoteItemMock(4, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $this->setTestConditions([
          'extend_enabled' => false,
          'existing_quote_items' => [
            $merchantQuoteItem,
            $existingWarrantyQuoteItem1yr
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($existingWarrantyQuoteItem1yr, 0);
        $this->expectRemoveItemToBeCalledTimes(0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeAddItem($this->quoteMock, $newWarrantyQuoteItem2yr);
    }

    // extend + cart balancing are enabled and item added is a merchant product -> do nothing
    public function testBeforeAddItemAddMerchantProductToEmptyCart()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $this->setTestConditions([
          'extend_enabled' => true,
          'existing_quote_items' => []
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectRemoveItemToBeCalledTimes(0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeAddItem($this->quoteMock, $merchantQuoteItem);
    }

    // extend + cart balancing are enabled and item added is a corresponding warranty product
    // for a matching cart item with matching qtys -> do nothing
    public function testBeforeAddItemAddWarrantyToCartWithCorrespondingMerchantItem()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(1, 'merchant_product_A');
        $warrantyQuoteItem = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A');
        $this->setTestConditions([
          'extend_enabled' => true,
          'existing_quote_items' => [
            $merchantQuoteItem
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($warrantyQuoteItem, 0);
        $this->expectRemoveItemToBeCalledTimes(0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeAddItem($this->quoteMock, $warrantyQuoteItem);
    }

    // extend + cart balancing are enabled and item added is 4x a corresponding warranty product for
    // a matching cart item with qty 5x and 1x existing cheaper warranty -> do nothing
    public function testBeforeAddItemAdd4WarrantyToCartWith5CorrespondingMerchantItemAnd1ExistingWarranty()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(5, 'merchant_product_A');
        $existingWarrantyQuoteItem1yr = $this->createQuoteItemMock(1, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $newWarrantyQuoteItem2yr = $this->createQuoteItemMock(4, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $this->setTestConditions([
          'extend_enabled' => true,
          'existing_quote_items' => [
            $merchantQuoteItem,
            $existingWarrantyQuoteItem1yr
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($existingWarrantyQuoteItem1yr, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($newWarrantyQuoteItem2yr, 0);
        $this->expectRemoveItemToBeCalledTimes(0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(0);
        $this->testSubject->beforeAddItem($this->quoteMock, $newWarrantyQuoteItem2yr);
    }

    // extend + cart balancing are enabled and item added is 4x a corresponding warranty product for
    // a matching cart item with qty 5x and 4x existing cheaper warranty -> decrease existing cheaper warranty qty 4x
    public function testBeforeAddItemAdd4WarrantyToCartWith5CorrespondingMerchantItemAnd4ExistingWarranty()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(5, 'merchant_product_A');
        $existingWarrantyQuoteItem1yr = $this->createQuoteItemMock(5, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $newWarrantyQuoteItem2yr = $this->createQuoteItemMock(4, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $this->setTestConditions([
          'extend_enabled' => true,
          'existing_quote_items' => [
            $merchantQuoteItem,
            $existingWarrantyQuoteItem1yr
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($existingWarrantyQuoteItem1yr, 1, 1);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($newWarrantyQuoteItem2yr, 0);
        $this->expectRemoveItemToBeCalledTimes(0);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(1);
        $this->testSubject->beforeAddItem($this->quoteMock, $newWarrantyQuoteItem2yr);
    }

    // extend + cart balancing are enabled and item added is 4x a corresponding warranty product for
    // a matching cart item with qty 5x and 2x existing cheaper warranty and 2x existing more expensive warranty
    // -> remove existing cheaper warranty entirely and decrease existing more expensive warranty qty 1x
    public function testBeforeAddItemAdd4WarrantyToCartWith5CorrespondingMerchantItemAnd2ExistingCheaperWarrantyAnd2ExistingMoreExpensiveWarranty()
    {
        $merchantQuoteItem = $this->createQuoteItemMock(5, 'merchant_product_A');
        $existingWarrantyQuoteItem1yr = $this->createQuoteItemMock(2, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 599);
        $existingWarrantyQuoteItem3yr = $this->createQuoteItemMock(2, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 1399);
        $newWarrantyQuoteItem2yr = $this->createQuoteItemMock(4, Extend::WARRANTY_PRODUCT_SKU, 'merchant_product_A', 999);
        $this->setTestConditions([
          'extend_enabled' => true,
          'existing_quote_items' => [
            $merchantQuoteItem,
            $existingWarrantyQuoteItem1yr,
            $existingWarrantyQuoteItem3yr
          ]
        ]);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($merchantQuoteItem, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($existingWarrantyQuoteItem1yr, 0);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($existingWarrantyQuoteItem3yr, 1, 1);
        $this->expectSetQtyToBeCalledOnQuoteItemTimesWith($newWarrantyQuoteItem2yr, 0);
        $actualArgs = [];
        $this->expectRemoveItemToBeCalledTimes(1, $actualArgs);
        $this->expectSetTotalsCollectedFlagToBeCalledTimes(2);
        $this->testSubject->beforeAddItem($this->quoteMock, $newWarrantyQuoteItem2yr);
        $this->assertEqualsCanonicalizing([$existingWarrantyQuoteItem1yr->getId()], $actualArgs);
    }

  /* =================================================================================================== */
  /* ========================== helper methods for setting up test conditions ========================== */
  /* =================================================================================================== */

    /**
     * @param array $conditions
     * 1. extend_enabled
     * 2. cart_balancing_enabled
     * 3. existing_quote_items
     */
    private function setTestConditions(
        array $conditions
    ) {
        $this->setExtendEnabled($conditions['extend_enabled'] ?? false);
        $this->setCartBalancingEnabled($conditions['cart_balancing_enabled'] ?? false);
        $this->setExistingQuoteItems($conditions['existing_quote_items'] ?? []);
    }

    private function setExtendEnabled(bool $condition)
    {
        if (isset($condition) && $condition) {
            $this->extendMock->method('isEnabled')->willReturn(true);
        } else {
            $this->extendMock->method('isEnabled')->willReturn(false);
        }
    }

    private function setCartBalancingEnabled(bool $condition)
    {
        if (isset($condition) && $condition) {
            $this->envViewModelMock->method('isCartBalancingEnabled')->willReturn(true);
        } else {
            $this->envViewModelMock->method('isCartBalancingEnabled')->willReturn(false);
        }
    }

    private function setExistingQuoteItems(array $quoteItems)
    {
        if (isset($quoteItems) && count($quoteItems) > 0) {
            $this->quoteMock->method('getAllVisibleItems')->willReturn($quoteItems);
            $this->quoteMock->method('getItemById')->will($this->returnValueMap(
                array_map(
                    function ($quoteItem) {
                        return [$quoteItem->getId(), $quoteItem];
                    },
                    $quoteItems
                )
            ));
        } else {
            $this->quoteMock->method('getAllVisibleItems')->willReturn([]);
            $this->quoteMock->method('getItemById')->willReturn(null);
        }
    }

  /* =================================================================================================== */
  /* ========================================= factory methods ========================================= */
  /* =================================================================================================== */

    /**
     * Create a mock quote item with the given config.
     * @param int $qty
     * @param string $productSku
     * @param string|null $correspondingMerchantProductSku
     * @param int|null $customPrice
     * @param string|null $leadToken
     * @param int|null $leadQty
     * @return Item | MockObject
     */
    private function createQuoteItemMock(
        int $qty,
        string $productSku,
        string $correspondingMerchantProductSku = null,
        int $customPrice = null,
        string $leadToken = null,
        int $leadQty = null
    ): Item | MockObject {
        $quoteItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getQty', 'getId', 'getOptionByCode', 'setQty'])
            ->addMethods(['getCustomPrice'])
            ->getMock();
        $quoteItemMock->method('getSku')->willReturn($productSku);
        $quoteItemMock->method('getQty')->willReturn($qty);
        $quoteItemMock->method('setQty')->willReturn($quoteItemMock);
        $quoteItemMock->method('getId')->willReturn(rand(1, 1000000));
        if (isset($customPrice)) {
            $quoteItemMock->method('getCustomPrice')->willReturn($customPrice);
        }
        /** @var array(Option | MockObject) */
        $quoteItemOptionsMap = [];
        if (isset($correspondingMerchantProductSku)) {
            $quoteItemOptionsMap[] = ['associated_product_sku', $this->createQuoteItemOptionMock($correspondingMerchantProductSku)];
        }
        if (isset($leadToken)) {
            $quoteItemOptionsMap[] = ['lead_token', $this->createQuoteItemOptionMock($leadToken)];
        }
        if (isset($leadQty)) {
            $quoteItemOptionsMap[] = ['lead_quantity', $this->createQuoteItemOptionMock($leadQty)];
        }
        $quoteItemMock->method('getOptionByCode')->will($this->returnValueMap($quoteItemOptionsMap));
        return $quoteItemMock;
    }

    /**
     * Create a mock quote item option with the given value.
     * @param string $value
     * @return Option | MockObject
     */
    private function createQuoteItemOptionMock(string $value): Option | MockObject
    {
        $quoteItemOptionMock = $this->createStub(Option::class);
        $quoteItemOptionMock->method('getValue')->willReturn($value);
        return $quoteItemOptionMock;
    }

  /* =================================================================================================== */
  /* ============================== helper methods for validating results ============================== */
  /* =================================================================================================== */

    /**
     * Closes over pointer to array of actual args passed to removeItem during execution
     * of the function being tested. This can be used to validate the args passed to removeItem.
     *
     * @param int $times
     * @param array $actualArgs
     */
    private function expectRemoveItemToBeCalledTimes(int $times, array &$actualArgs = [])
    {
        $this->quoteMock->expects($this->exactly($times))->method('removeItem')->will(
            $this->returnCallback(
                function ($itemId) use (&$actualArgs) {
                    $actualArgs[] = $itemId;
                }
            )
        );
    }

    /**
     * Expect setTotalsCollectedFlag to be called with the config provided.
     *
     * @param int $times
     */
    private function expectSetTotalsCollectedFlagToBeCalledTimes(int $times)
    {
        $this->quoteMock->expects($times ? $this->exactly($times) : $this->never())->method('setTotalsCollectedFlag')->with(false);
    }

    /**
     * Expect Quote\Item::setQty to be called on the item provided with the config provided
     */
    private function expectSetQtyToBeCalledOnQuoteItemTimesWith(Item | MockObject $quoteItem, int $times, int $newQty = null)
    {
        if (isset($newQty)) {
            $quoteItem->expects($this->exactly($times))->method('setQty')->with($newQty);
        } else {
            $quoteItem->expects($this->never())->method('setQty');
        }
    }
}
