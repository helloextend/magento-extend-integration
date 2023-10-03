<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Observer\SaveOrderBeforeSalesModelQuote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Framework\DataObject\Copy;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class SaveOrderBeforeSalesModelQuoteTest extends TestCase
{
    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var CartExtensionInterface|MockObject
     */
    private $quoteExtensionAttributes;

    /**
     * @var OrderExtensionInterface|MockObject
     */
    private $orderExtensionAttributes;

    /**
     * @var Store|MockObject
     */
    private $store;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ExtendService|MockObject
     */
    private $extendService;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CartExtensionFactory|MockObject
     */
    private $cartExtensionFactory;

    /**
     * @var OrderExtensionFactory|MockObject
     */
    private $orderExtensionFactory;

    /**
     * @var Copy|MockObject
     */
    private $objectCopyService;

    /**
     * @var SaveOrderBeforeSalesModelQuote
     */
    private $import;

    protected function setUp(): void
    {
        $this->orderExtensionAttributes = $this->createMock(OrderExtensionInterface::class);
        $this->quoteExtensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->onlyMethods(['getShippingProtection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->order = $this->createMock(Order::class);
        $this->quote = $this->createMock(Quote::class);
        $this->event = $this->createMock(Event::class);
        $this->event
            ->method('getData')
            ->willReturn($this->returnValueMap([
                ['order', null, $this->order],
                ['quote', null, $this->quote],
            ]));
        $this->observer = $this->createConfiguredMock(Observer::class, [
            'getEvent' => $this->event,
        ]);
        $this->store = $this->createMock(Store::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->extendService = $this->createMock(ExtendService::class);
        $this->integration = $this->createMock(Integration::class);
        $this->storeManager = $this->createConfiguredMock(StoreManagerInterface::class, [
            'getStore' => $this->store
        ]);
        $this->cartExtensionFactory = $this->createMock(CartExtensionFactory::class);
        $this->orderExtensionFactory = $this->createMock(OrderExtensionFactory::class);
        $this->objectCopyService = $this->createMock(Copy::class);
        $this->import = new SaveOrderBeforeSalesModelQuote(
            $this->logger,
            $this->extendService,
            $this->integration,
            $this->storeManager,
            $this->cartExtensionFactory,
            $this->orderExtensionFactory,
            $this->objectCopyService
        );
    }

    public function testCartExtensionFactoryDoesNotCreateQuoteExtensionAttributesAndCopiesExtensionAttributesFromQuoteOntoOrderIfExtendIsEnabledAndQuoteExtensionAttributesAreNotNullAndGeneratedQuoteExtensionAttributesHaveShippingProtection()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent');
        $this->event
            ->expects($this->exactly(2))
            ->method('getData');
        $this->order
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->orderExtensionAttributes);
        $this->quote
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes
            ->expects($this->once())
            ->method('getShippingProtection')
            ->willReturn([]);
        $this->orderExtensionFactory
            ->expects($this->never())
            ->method('create');
        $this->order
            ->expects($this->once())
            ->method('setExtensionAttributes');
        $this->objectCopyService
            ->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                'extend_integration_sales_convert_quote',
                'to_order',
                $this->quote,
                $this->order
            );
        $this->import->execute($this->observer);
    }

    public function testCartExtensionFactoryCreatesQuoteExtensionAttributesAndCopiesExtensionAttributesFromQuoteOntoOrderIfExtendIsEnabledAndQuoteExtensionAttributesAreNullAndGeneratedQuoteExtensionAttributesHaveShippingProtection()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent');
        $this->event
            ->expects($this->exactly(2))
            ->method('getData');
        $this->quote
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->cartExtensionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes
            ->expects($this->once())
            ->method('getShippingProtection')
            ->willReturn([]);
        $this->order
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->orderExtensionFactory
            ->expects($this->once())
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
                'extend_integration_sales_convert_quote',
                'to_order',
                $this->quote,
                $this->order
            );
        $this->import->execute($this->observer);
    }

    public function testSkipsExecutionIfQuoteExtensionAttributesDoNotHaveShippingProtection()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent');
        $this->event
            ->expects($this->exactly(2))
            ->method('getData');
        $this->quote
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->cartExtensionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes
            ->expects($this->once())
            ->method('getShippingProtection')
            ->willReturn(null);
        $this->objectCopyService
            ->expects($this->never())
            ->method('copyFieldsetToTarget');
        $this->import->execute($this->observer);
    }

    public function testSkipsExecutionIfExtendIsNotEnabled()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->objectCopyService
            ->expects($this->never())
            ->method('copyFieldsetToTarget');
        $this->import->execute($this->observer);
    }

    public function testLogsErrorsToLoggingService()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent');
        $this->event
            ->expects($this->exactly(2))
            ->method('getData');
        $this->quote
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->cartExtensionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes
            ->expects($this->once())
            ->method('getShippingProtection')
            ->willReturn([]);
        $this->order
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->orderExtensionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->orderExtensionAttributes);
        $this->order
            ->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->orderExtensionAttributes);
        $this->objectCopyService
            ->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->willThrowException(new Exception());
        $this->logger
            ->expects($this->once())
            ->method('error');
        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService');
        $this->storeManager
            ->expects($this->once())
            ->method('getStore');
        $this->import->execute($this->observer);
    }
}
