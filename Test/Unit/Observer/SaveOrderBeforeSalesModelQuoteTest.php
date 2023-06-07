<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Observer\SaveOrderBeforeSalesModelQuote;

use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Framework\DataObject\Copy;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveOrderBeforeSalesModelQuoteTest extends TestCase
{
    /**
     * @var SaveOrderBeforeSalesModelQuote
     */
    private $import;

    /**
     * @var Copy|MockObject
     */
    private $objectCopyService;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var OrderExtensionFactory|MockObject
     */
    private $orderExtensionFactory;

    /**
     * @var CartExtensionFactory|MockObject
     */
    private $cartExtensionFactory;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var OrderExtensionInterface|MockObject
     */
    protected $orderExtensionAttributes;

    /**
     * @var CartExtensionInterface|MockObject
     */
    protected $quoteExtensionAttributes;

    protected function setUp(): void
    {
        $this->orderExtensionAttributes = $this->getMockBuilder(OrderExtensionInterface::class)
            ->setMethods(['getShippingProtection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteExtensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->setMethods(['getShippingProtection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderExtensionFactory = $this->getMockBuilder(OrderExtensionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderExtensionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->orderExtensionAttributes);
        $this->cartExtensionFactory = $this->getMockBuilder(CartExtensionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartExtensionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteExtensionAttributes);
        $this->objectCopyService = $this->createMock(Copy::class);
        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods(['getExtensionAttributes', 'setExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->createMock(Quote::class);
        $this->event = $this->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $map = [['order', null, $this->order], ['quote', null, $this->quote]];
        $this->event
            ->expects($this->any())
            ->method('getData')
            ->willReturn($this->returnValueMap($map));
        $this->observer = $this->createPartialMock(Observer::class, ['getEvent']);
        $this->observer
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->objectManager = new ObjectManager($this);
        $this->import = $this->objectManager->getObject(SaveOrderBeforeSalesModelQuote::class, [
            'objectCopyService' => $this->objectCopyService,
            'orderExtensionFactory' => $this->orderExtensionFactory,
            'cartExtensionFactory' => $this->cartExtensionFactory,
        ]);
    }

    public function testReturnsExpected()
    {
        $this->order
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->orderExtensionAttributes);
        $this->quote
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes
            ->expects($this->any())
            ->method('getShippingProtection')
            ->willReturn([]);
        $this->orderExtensionFactory->expects($this->never())->method('create');
        $this->order->expects($this->once())->method('setExtensionAttributes');
        $this->objectCopyService
            ->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                $this->equalTo('extend_integration_sales_convert_quote'),
                $this->equalTo('to_order'),
                $this->equalTo($this->quote),
                $this->equalTo($this->order)
            );
        $this->import->execute($this->observer);
    }

    public function testReturnsExpectedIfExtensionAttributesAreNull()
    {
        $this->quote
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->cartExtensionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes
            ->expects($this->any())
            ->method('getShippingProtection')
            ->willReturn([]);
        $this->order
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->orderExtensionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->orderExtensionAttributes);
        $this->order
            ->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->orderExtensionAttributes);
        $this->objectCopyService
            ->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                $this->equalTo('extend_integration_sales_convert_quote'),
                $this->equalTo('to_order'),
                $this->equalTo($this->quote),
                $this->equalTo($this->order)
            );
        $this->import->execute($this->observer);
    }
}
