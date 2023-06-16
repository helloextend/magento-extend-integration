<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        $this->quoteRespository = $this->getMockBuilder(
            \Magento\Quote\Api\CartRepositoryInterface::class
        )
            ->onlyMethods('save')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->ProductRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->onlyMethods('get')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->itemFactory = $this->getMockBuilder(\Magento\Quote\Model\Quote\ItemFactory::class)
            ->onlyMethods('create')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->itemOptionFactory = $this->getMockBuilder(
            \Magento\Quote\Model\Quote\Item\OptionFactory::class
        )
            ->onlyMethods('create')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->onlyMethods('getQuote')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->integration = $this->getMockBuilder(
            \Extend\Integration\Service\Api\Integration::class
        )
            ->onlyMethods('logErrorToLoggingService')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->onlyMethods('getStore')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods('error')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testUpsert()
    {
    }
}
