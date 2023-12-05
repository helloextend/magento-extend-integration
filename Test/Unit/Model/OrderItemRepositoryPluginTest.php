<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Model;

use Extend\Integration\Plugin\Model\OrderItemRepositoryPlugin;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection as QuoteItemCollection;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;
use Extend\Integration\Service\Extend;
use Extend\Integration\Model\ProductProtectionFactory;
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
     * @var ExtensibleDataInterface|MockObject
     */
    private $orderItem;

    /**
     * @var ExtensibleDataInterface|MockObject
     */
    private $quoteItem;

    /**
     * @var OrderItemExtensionFactory|MockObject
     */
    private $orderItemExtensionFactory;

     /**
     * @var OrderExtensionInterface|MockObject
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
        $this->orderItem = $this->getMockBuilder(ExtensibleDataInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getSku', 'getQuoteItemId', 'setExtensionAttributes'])
            ->getMock();
        $this->quoteItem = $this->getMockBuilder(ExtensibleDataInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProduct', 'getOptions', 'getOptionByCode'])
            ->getMock();
        $this->quoteItem
            ->method('getProduct')
            ->willReturn($this->quoteItem);
        $this->orderItemSearchResult = $this->createConfiguredMock(OrderItemSearchResultInterface::class, [
          'getItems' => [$this->orderItem]
        ]);
        $this->orderItemExtensionFactory = $this->createMock(OrderItemExtensionFactory::class);
        $this->orderItemExtensions = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setProductProtection'])
            ->getMockForAbstractClass();
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
