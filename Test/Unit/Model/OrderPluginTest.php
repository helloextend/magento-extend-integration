<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Model;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Service\Extend;
use Extend\Integration\Plugin\Model\OrderPlugin;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderPluginTest extends TestCase
{
    /**
     * @var Order|MockObject
     */
    private $subject;

    /**
     * @var InvoiceCollection|MockObject
     */
    private $invoiceCollectionResult;

    /**
     * @var ExtensibleDataInterface|MockObject
     */
    private $invoiceCollectionItem;

    /**
     * @var int
     */
    private $invoiceId = 1;

    /**
     * @var CreditmemoCollection|MockObject
     */
    private $creditmemoCollectionResult;

    /**
     * @var int
     */
    private $creditmemoId = 1;

    /**
     * @var ExtensibleDataInterface|MockObject
     */
    private $creditmemoCollectionItem;

    /**
     * @var int
     */
    private $orderId = 1;

    /**
     * @var string
     */
    private $orderIncrementId = '1';

    /**
     * @var ExtensibleDataInterface|MockObject
     */
    private $orderResult;

    /**
     * @var OrderItemCollection|MockObject
     */
    private $orderItemsCollection;

    /**
     * @var OrderItem|MockObject
     */
    private $regularProductOrderItem;

    /**
     * @var OrderItem|MockObject
     */
    private $extendPlanOrderItem;

    /**
     * @var ShippingProtectionTotalRepositoryInterface|MockObject
     */
    private $shippingProtectionTotalRepository;

    /**
     * @var Extend|MockObject
     */
    private $extend;

    /**
     * @var OrderPlugin
     */
    private $orderPlugin;

    protected function setUp(): void
    {
        $this->subject = $this->createMock(Order::class);
        $this->invoiceCollectionItem = $this->getMockBuilder(ExtensibleDataInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMock();
        $this->invoiceCollectionResult = $this->createConfiguredMock(InvoiceCollection::class, [
            'getItems' => [$this->invoiceCollectionItem]
        ]);
        $this->invoiceCollectionItem
            ->method('getId')
            ->willReturn($this->invoiceId);
        $this->creditmemoCollectionItem = $this->getMockBuilder(ExtensibleDataInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMock();
        $this->creditmemoCollectionResult = $this->createConfiguredMock(CreditmemoCollection::class, [
            'getItems' => [$this->creditmemoCollectionItem]
        ]);
        $this->creditmemoCollectionItem
            ->method('getId')
            ->willReturn($this->creditmemoId);
        $this->orderResult = $this->getMockBuilder(ExtensibleDataInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMock();
        $this->orderResult
            ->method('getId')
            ->willReturn($this->orderId);
        $this->orderItemsCollection = $this->createMock(OrderItemCollection::class);
        $this->subject
            ->method('getItemsCollection')
            ->willReturn($this->orderItemsCollection);
        $this->regularProductOrderItem = $this->createConfiguredMock(OrderItem::class, [
            'getName' => 'Regular Product'
        ]);
        $this->extendPlanOrderItem = $this->createConfiguredMock(OrderItem::class, [
          'getName' => Extend::WARRANTY_PRODUCT_NAME
        ]);
        $this->shippingProtectionTotalRepository = $this->createMock(ShippingProtectionTotalRepositoryInterface::class);
        $this->extend = $this->createMock(Extend::class);
        $this->orderPlugin = new OrderPlugin(
            $this->shippingProtectionTotalRepository,
            $this->extend
        );
    }

    public function testAfterGetInvoiceCollectionWhenExtendIsNotEnabledSkipsExecution()
    {
        $this->extend
            ->method('isEnabled')
            ->willReturn(false);
        $this->shippingProtectionTotalRepository
            ->expects($this->never())
            ->method('getAndSaturateExtensionAttributes');
        $this->orderPlugin->afterGetInvoiceCollection($this->subject, $this->invoiceCollectionResult);
    }

    public function testAfterGetInvoiceCollectionWhenExtendIsEnabledSetsExtensionAttributes()
    {
        $this->extend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->invoiceCollectionResult
            ->expects($this->once())
            ->method('getItems');
        $this->invoiceCollectionItem
            ->expects($this->once())
            ->method('getId');
        $this->shippingProtectionTotalRepository
            ->expects($this->once())
            ->method('getAndSaturateExtensionAttributes')
            ->with(
              $this->invoiceId,
              ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID,
              $this->invoiceCollectionItem
          );
        $this->orderPlugin->afterGetInvoiceCollection($this->subject, $this->invoiceCollectionResult);
    }


    public function testAfterGetCreditmemosCollectionWhenExtendIsNotEnabledSkipsExecution()
    {
        $this->extend
            ->method('isEnabled')
            ->willReturn(false);
        $this->shippingProtectionTotalRepository
            ->expects($this->never())
            ->method('getAndSaturateExtensionAttributes');
        $this->orderPlugin->afterGetCreditmemosCollection($this->subject, $this->creditmemoCollectionResult);
    }

    public function testAfterGetCreditmemosCollectionWhenResultIsFalse()
    {
        $this->extend
            ->method('isEnabled')
            ->willReturn(true);
        $this->shippingProtectionTotalRepository
            ->expects($this->never())
            ->method('getAndSaturateExtensionAttributes');
        $this->orderPlugin->afterGetCreditmemosCollection($this->subject, false);
    }

    public function testAfterGetCreditmemosCollectionWhenExtendIsEnabledSetsExtensionAttributes()
    {
        $this->extend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->creditmemoCollectionResult
            ->expects($this->once())
            ->method('getItems');
        $this->creditmemoCollectionItem
            ->expects($this->once())
            ->method('getId');
        $this->shippingProtectionTotalRepository
            ->expects($this->once())
            ->method('getAndSaturateExtensionAttributes')
            ->with(
              $this->creditmemoId,
              ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID,
              $this->creditmemoCollectionItem
          );
          $this->orderPlugin->afterGetCreditmemosCollection($this->subject, $this->creditmemoCollectionResult);
    }

    public function testAfterLoadByIncrementIdWhenExtendIsNotEnabledSkipsExecution()
    {
        $this->extend
            ->method('isEnabled')
            ->willReturn(false);
        $this->shippingProtectionTotalRepository
            ->expects($this->never())
            ->method('getAndSaturateExtensionAttributes');
        $this->orderPlugin->afterLoadByIncrementId($this->subject, $this->orderResult, $this->orderIncrementId);
    }

    public function testAfterLoadByIncrementIdWhenExtendIsEnabledSetsExtensionAttributes()
    {
        $this->extend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->orderResult
            ->expects($this->once())
            ->method('getId');
        $this->shippingProtectionTotalRepository
            ->expects($this->once())
            ->method('getAndSaturateExtensionAttributes')
            ->with(
              $this->orderId,
              ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID,
              $this->orderResult
          );
        $this->orderPlugin->afterLoadByIncrementId($this->subject, $this->orderResult, $this->orderIncrementId);
    }

    public function testAfterCanReorderReturnsTrueWhenOrderDoesNotContainExtendPlans()
    {
        $this->orderItemsCollection
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->regularProductOrderItem]);
        $this->assertTrue($this->orderPlugin->afterCanReorder($this->subject, true));
    }

    public function testAfterCanReorderReturnsFalseWhenOrderContainsExtendPlans()
    {
        $this->orderItemsCollection
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->regularProductOrderItem, $this->extendPlanOrderItem]);
        $this->assertFalse($this->orderPlugin->afterCanReorder($this->subject, true));
    }
}
