<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Model;

use Extend\Integration\Model\ProductProtection;
use Extend\Integration\Service\Api\Integration;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Setup\Exception;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductProtectionTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private CartRepositoryInterface $quoteRepository;

    protected function setUp(): void
    {
        $this->quoteRespository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->onlyMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->itemFactory = $this->getMockBuilder(ItemFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->itemOptionFactory = $this->getMockBuilder(OptionFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->onlyMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->integration = $this->getMockBuilder(Integration::class)
            ->onlyMethods(['logErrorToLoggingService'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productProtection = new ProductProtection(
            $this->quoteRespository,
            $this->productRepository,
            $this->itemFactory,
            $this->itemOptionFactory,
            $this->checkoutSession,
            $this->integration,
            $this->storeManager,
            $this->logger
        );

        $this->upsertParameters = [
            'quantity' => 2,
            'cartItemId' => 289,
            'productId' => 314,
            'planId' => '038e65ca-5d3d-4ddf-a294-1b12e2dd8781',
            'price' => 1.25,
            'term' => 24,
            'coverageType' => 'base',
            'leadToken' => '4d172caf-d12d-40e2-bbf9-661af6337823',
            'listPrice' => 32.45,
            'orderOfferPlanId' => '86fb407c-0940-4a1b-ae23-dc98c3f1a235',
        ];
    }

    public function testUpsertWhenPriceIsZero()
    {
        $this->productProtection
            ->upsert(explode(',', array_values($this->upsertParameters)))
            ->willThrowException(new Exception());
    }
}
