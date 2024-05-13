<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Controller\Minicart;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Extend\Integration\Controller\Minicart\Categories;

class CategoriesTest extends TestCase
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Categories
     */
    protected $categories;

    protected function setUp(): void
    {
        $context = $this->createStub(Context::class);
        $this->resultJsonFactory = $this->createStub(JsonFactory::class);
        $this->checkoutSession = $this->createStub(CheckoutSession::class);
        $this->categoryRepository = $this->createStub(CategoryRepositoryInterface::class);

        $this->categories = new Categories(
          $context,
          $this->resultJsonFactory,
          $this->checkoutSession,
          $this->categoryRepository
      );
    }

    public function testGetCategories()
    {
        $itemId = rand(1, 1000000);
        $product = $this->createStub(Product::class);
        $product->method('getCategoryIds')->willReturn([1]);
        $quoteItemMock = $this->getMockBuilder(Item::class)
          ->disableOriginalConstructor()
          ->onlyMethods(['getProduct', 'getId'])
          ->getMock();
        $quoteItemMock->method('getProduct')->willReturn($product);
        $quoteItemMock->method('getId')->willReturn($itemId);
        $quote = $this->createStub(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$quoteItemMock]);

        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $categoryMock = $this->createStub(CategoryInterface::class);
        $categoryMock->method('getName')->willReturn('Clothes');
        $this->categoryRepository->method('get')->willReturn($categoryMock);

        $jsonResult = $this->createStub(Json::class);

        $items = [];
        $items[$itemId] = 'Clothes';
        $jsonResult->method('setData')->willReturn($items);

        $this->resultJsonFactory->method('create')->willReturn($jsonResult);

        $this->assertEquals($items, $this->categories->execute());
    }

    public function testGetCategoriesNoItems()
    {
        $quote = $this->createStub(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([]);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $jsonResult = $this->createStub(Json::class);
        $jsonResult->method('setData')->willReturn([]);

        $this->resultJsonFactory->method('create')->willReturn($jsonResult);

        $this->assertEquals([], $this->categories->execute());
    }

    public function testGetCategoriesWhereItemHasNoCategories()
    {
        $itemId = rand(1, 1000000);
        $product = $this->createStub(Product::class);
        $product->method('getCategoryIds')->willReturn([]);
        $quoteItemMock = $this->getMockBuilder(Item::class)
          ->disableOriginalConstructor()
          ->onlyMethods(['getProduct', 'getId'])
          ->getMock();
        $quoteItemMock->method('getProduct')->willReturn($product);
        $quoteItemMock->method('getId')->willReturn($itemId);
        $quote = $this->createStub(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$quoteItemMock]);

        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $jsonResult = $this->createStub(Json::class);
        $jsonResult->method('setData')->willReturn([]);
        $this->resultJsonFactory->method('create')->willReturn($jsonResult);

        $this->assertEquals([], $this->categories->execute());
    }

    public function testGetCategoriesWhereItemProductDoesNotExist() {
        $quoteItemMock = $this->getMockBuilder(Item::class)
          ->disableOriginalConstructor()
          ->onlyMethods(['getProduct'])
          ->getMock();
        $quoteItemMock->method('getProduct')->willReturn(null);
        $quote = $this->createStub(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$quoteItemMock]);

        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $jsonResult = $this->createStub(Json::class);
        $jsonResult->method('setData')->willReturn([]);
        $this->resultJsonFactory->method('create')->willReturn($jsonResult);

        $this->assertEquals([], $this->categories->execute());
    }
}
