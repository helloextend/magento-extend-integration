<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Model;

use Extend\Integration\Plugin\Model\OrderItemRepositoryPlugin;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemExtensionInterface;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection as QuoteItemCollection;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;
use Extend\Integration\Service\Extend;
use Extend\Integration\Model\ProductProtection;
use Extend\Integration\Model\ProductProtectionFactory;
use Extend\Integration\Api\ProductProtectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderItemRepositoryPluginTest extends TestCase
{
    /**
     * @var OrderItemRepositoryInterface|MockObject
     */
    private $subject;

    /**
     * @var OrderItemSearchResultInterface|MockObject
     */
    private $orderItemSearchResult;

    /**
     * @var OrderItemInterface|MockObject
     */
    private $orderItem;

    /**
     * @var QuoteItem|MockObject
     */
    private $quoteItem;

    /**
     * @var OrderItemExtensionFactory|MockObject
     */
    private $orderItemExtensionFactory;

     /**
      * @var OrderItemExtensionInterface|MockObject
      */
    private $orderItemExtensions;

    /**
     * @var QuoteItemCollectionFactory|MockObject
     */
    private $quoteItemCollectionFactory;

    /**
     * @var QuoteItemCollection|MockObject
     */
    private $quoteItemCollection;

    /**
     * @var ProductProtectionFactory|MockObject
     */
    private $productProtectionFactory;

    /**
     * @var ProductProtection|MockObject
     */
    private $productProtection;

    /**
     * @var Extend|MockObject
     */
    private $extend;

    /**
     * @var OrderItemRepositoryPlugin
     */
    private $orderItemRepositoryPlugin;

    protected function setUp(): void
    {
        $this->subject = $this->createMock(OrderItemRepositoryInterface::class);
        $this->orderItem = $this->createMock(OrderItemInterface::class);
        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProduct', 'getOptions', 'getOptionByCode'])
            ->getMock();
        $this->quoteItem
            ->method('getProduct')
            ->willReturn($this->quoteItem);
        $this->orderItemSearchResult = $this->createConfiguredMock(OrderItemSearchResultInterface::class, [
          'getItems' => [$this->orderItem]
        ]);
        $this->orderItemExtensionFactory = $this->createMock(OrderItemExtensionFactory::class);
        $this->orderItemExtensions = $this->createMock(OrderItemExtensionInterface::class);
        $this->quoteItemCollectionFactory = $this->createMock(QuoteItemCollectionFactory::class);
        $this->quoteItemCollection = $this->getMockBuilder(QuoteItemCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToSelect', 'addFieldToFilter', 'getFirstItem'])
            ->getMock();
        $this->quoteItemCollection
            ->method('addFieldToSelect')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection
            ->method('addFieldToFilter')
            ->willReturn($this->quoteItemCollection);
        $this->productProtectionFactory = $this->createMock(ProductProtectionFactory::class);
        $this->productProtection = $this->createMock(ProductProtection::class);
        $this->extend = $this->createMock(Extend::class);
        $this->orderItemRepositoryPlugin = new OrderItemRepositoryPlugin(
            $this->orderItemExtensionFactory,
            $this->quoteItemCollectionFactory,
            $this->productProtectionFactory,
            $this->extend
        );
    }

    public function testAfterGetListWhenExtendIsNotEnabledSkipsExecution()
    {
        $this->extend
            ->method('isEnabled')
            ->willReturn(false);
        $this->orderItemSearchResult
            ->expects($this->never())
            ->method('getItems');
        $this->orderItemExtensions
            ->expects($this->never())
            ->method('setProductProtection');
        $this->orderItemRepositoryPlugin->afterGetList($this->subject, $this->orderItemSearchResult);
    }

    public function testAfterGetListWhenOrderItemIsNotProductionProtectionSkipsExecution()
    {
        $this->extend
            ->method('isEnabled')
            ->willReturn(true);
        $this->orderItem
            ->method('getSku')
            ->willReturn('random-sku');
        $this->quoteItemCollectionFactory
            ->expects($this->never())
            ->method('create');
        $this->orderItemExtensions
            ->expects($this->never())
            ->method('setProductProtection');
        $this->orderItem
            ->expects($this->never())
            ->method('setExtensionAttributes');
        $this->orderItemRepositoryPlugin->afterGetList($this->subject, $this->orderItemSearchResult);
    }

    public function testAfterGetListWithLegacyExtendProductProtectionSku()
    {
        $this->extend
            ->method('isEnabled')
            ->willReturn(true);
        $this->orderItem
            ->method('getSku')
            ->willReturn('extend-protection-plan');
        $this->quoteItemCollectionFactory
            ->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->orderItemExtensionFactory
            ->method('create')
            ->willReturn($this->orderItemExtensions);
        $this->productProtectionFactory
            ->method('create')
            ->willReturn($this->productProtection);
        $this->quoteItemCollection
            ->method('getFirstItem')
            ->willReturn($this->quoteItem);
        $this->orderItemExtensions
            ->expects($this->once())
            ->method('setProductProtection');
        $this->orderItem
            ->expects($this->once())
            ->method('setExtensionAttributes');
        $this->orderItemRepositoryPlugin->afterGetList($this->subject, $this->orderItemSearchResult);
    }

    public function testAfterGetListWithExtendProductProtectionSku()
    {
        $this->extend
            ->method('isEnabled')
            ->willReturn(true);
        $this->orderItem
            ->method('getSku')
            ->willReturn('xtd-pp-pln');
        $this->quoteItemCollectionFactory
            ->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->orderItemExtensionFactory
            ->method('create')
            ->willReturn($this->orderItemExtensions);
        $this->productProtectionFactory
            ->method('create')
            ->willReturn($this->productProtection);
        $this->quoteItemCollection
            ->method('getFirstItem')
            ->willReturn($this->quoteItem);
        $this->orderItemExtensions
            ->expects($this->once())
            ->method('setProductProtection');
        $this->orderItem
            ->expects($this->once())
            ->method('setExtensionAttributes');
        $this->orderItemRepositoryPlugin->afterGetList($this->subject, $this->orderItemSearchResult);
    }
}
