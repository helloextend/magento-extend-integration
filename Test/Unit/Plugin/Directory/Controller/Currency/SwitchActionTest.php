<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Directory\Controller\Currency;

use Magento\Directory\Controller\Currency\SwitchAction as SwitchActionParent;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\CartItemInterface;
use Extend\Integration\Service\Extend;
use Extend\Integration\Model\ShippingProtectionTotalRepository;
use Extend\Integration\Plugin\Directory\Controller\Currency\SwitchAction;
use PHPUnit\Framework\TestCase;

class SwitchActionTest extends TestCase
{
    /** @var SwitchAction */
    private $switchAction;

    /** @var SwitchActionParent */
    private $switchActionParent;

    /** @var Session */
    private $checkoutSession;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var ShippingProtectionTotalRepository */
    private $shippingProtectionTotalRepository;

    /** @var Quote */
    private $quote;

    /** @var CartItemInterface */
    private $merchantItem;

    /** @var CartItemInterface */
    private $extendProduct;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->createStub(Session::class);
        $this->cartRepository = $this->createStub(CartRepositoryInterface::class);
        $this->shippingProtectionTotalRepository = $this->createStub(ShippingProtectionTotalRepository::class);

        // Create a mock Extend Product
        $this->extendProduct = $this->createStub(CartItemInterface::class);

        // Since we leverage Magento "magic" methods, we need to add them explicitly for the test
        $this->extendProduct = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getSku', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->extendProduct->expects($this->any())
            ->method('getSku')
            ->willReturn(Extend::WARRANTY_PRODUCT_SKU);
        $this->extendProduct->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->merchantItem = $this->createStub(CartItemInterface::class);
        $this->merchantItem->method('getSku')->willReturn('merchant-item');

        $this->quote = $this->createStub(Quote::class);
        $this->quote->method('collectTotals')->willReturn($this->quote);
        $this->checkoutSession->method('getQuote')->willReturn($this->quote);

        $this->switchActionParent = $this->createStub(SwitchActionParent::class);

        $this->switchAction = new SwitchAction(
            $this->checkoutSession,
            $this->cartRepository,
            $this->shippingProtectionTotalRepository
        );
    }

    public function testRemovesShippingProtectionTotals()
    {
        $this->shippingProtectionTotalRepository->expects($this->once())
            ->method('delete');

        $this->switchAction->beforeExecute($this->switchActionParent);
    }

    public function testRemovesExtendProductsFromCartAndSavesQuote()
    {
        $this->quote->method('getItems')->willReturn([
            $this->merchantItem,
            $this->extendProduct
        ]);

        $this->quote->expects($this->once())->method('removeItem')->with($this->extendProduct->getId());

        $this->cartRepository->expects($this->once())->method('save')->with($this->quote);

        $this->switchAction->beforeExecute($this->switchActionParent);
    }

    public function testShouldNotSaveQuoteWhenNoExtendProductsAreInCart()
    {
        $this->quote->method('getItems')->willReturn([
          $this->merchantItem
        ]);

        $this->cartRepository->expects($this->never())->method('save');

        $this->switchAction->beforeExecute($this->switchActionParent);
    }

    public function testShouldNotSaveQuoteWhenNoItemsAreInCart()
    {
        $this->quote->method('getItems')->willReturn([]);

        $this->cartRepository->expects($this->never())->method('save');

        $this->switchAction->beforeExecute($this->switchActionParent);
    }
}
