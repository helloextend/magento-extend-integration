<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Extend\Integration\Observer\SalesOrderSaveAfter;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Registry;
use Exception;

class SalesOrderSaveAfterTest extends TestCase
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
    private $orderMock;

    /**
     * @var string
     */
    private $mockCreateDate = '2021-01-01 00:00:00';

    /**
     * @var string
     */
    private $mockUpdateDate = '2021-02-03 00:00:00';

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
     * @var OrderObserverHandler|MockObject
     */
    private $orderObserverHandler;

    /**
     * @var Registry|MockObject
     */
    private $registry;

    /**
     * @var SalesOrderSaveAfter
     */
    private $import;

    protected function setUp(): void
    {
        $this->orderMock = $this->createStub(Order::class);
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->event
            ->method('getOrder')
            ->willReturn($this->orderMock);
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
        $this->orderObserverHandler = $this->createMock(OrderObserverHandler::class);
        $this->registry = $this->createMock(Registry::class);
        $this->import = new SalesOrderSaveAfter(
            $this->logger,
            $this->extendService,
            $this->integration,
            $this->storeManager,
            $this->orderObserverHandler,
            $this->registry
        );
    }

    public function testReturnsEarlyIfInvoiceHasBeenCreated()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->registry
            ->expects($this->once())
            ->method('registry')
            ->with('extend.invoice.created')
            ->willReturn(true);
        $this->orderObserverHandler
            ->expects($this->never())
            ->method('execute');
        $this->import->execute($this->observer);
    }

    public function testSkipsExecutionWhenExtendIsEnabledAndOrderIsBeingCreated()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent');
        $this->event
            ->expects($this->once())
            ->method('getOrder');
        $this->orderMock
            ->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($this->mockCreateDate);
        $this->orderMock
            ->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($this->mockCreateDate);
        $this->orderObserverHandler
            ->expects($this->never())
            ->method('execute');
        $this->import->execute($this->observer);
    }

    public function testExecutesOrdersObserveHandlerWhenExtendIsEnabledAndOrderIsBeingUpdated()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent');
        $this->event
            ->expects($this->once())
            ->method('getOrder');
        $this->orderMock
            ->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($this->mockCreateDate);
        $this->orderMock
            ->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($this->mockUpdateDate);
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_update'],
                    'type' => 'middleware',
                ],
                $this->orderMock,
                []
            );
        $this->import->execute($this->observer);
    }

    public function testSkipsExecutionIfExtendIsNotEnabled()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->orderObserverHandler
            ->expects($this->never())
            ->method('execute');
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
            ->expects($this->once())
            ->method('getOrder');
        $this->orderMock
            ->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($this->mockCreateDate);
        $this->orderMock
            ->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($this->mockUpdateDate);
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->storeManager
            ->expects($this->once())
            ->method('getStore');
        $this->logger
            ->expects($this->once())
            ->method('error');
        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService');
        $this->import->execute($this->observer);
    }
}
