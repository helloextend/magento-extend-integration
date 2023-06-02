<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Extend\Integration\Observer\SalesOrderSaveAfter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class SalesOrderSaveAfterTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SalesOrderSaveAfter
     */
    private $import;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Store|MockObject
     */
    private $store;

    /**
     * @var OrderObserverHandler|MockObject
     */
    private $orderObserverHandler;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

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

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->orderObserverHandler = $this->getMockBuilder(OrderObserverHandler::class)
            ->setMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->integration = $this->getMockBuilder(Integration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->store = $this->getMockBuilder(Store::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->onlyMethods(['getCreatedAt', 'getUpdatedAt'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->event
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        $this->observer = $this->createPartialMock(Observer::class, ['getEvent']);
        $this->observer
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->objectManager = new ObjectManager($this);
        $this->import = $this->objectManager->getObject(SalesOrderSaveAfter::class, [
            'logger' => $this->logger,
            'orderObserverHandler' => $this->orderObserverHandler,
            'integration' => $this->integration,
            'storeManager' => $this->storeManager,
        ]);
    }

    public function testExecutesOrdersCreateHandlerWhenOrderIsBeingCreated()
    {
        $mockCreateDate = '2021-01-01 00:00:00';
        $mockUpdateDate = '2021-01-01 00:00:00';
        $this->orderMock
            ->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn($mockCreateDate);
        $this->orderMock
            ->expects($this->any())
            ->method('getUpdatedAt')
            ->willReturn($mockUpdateDate);
        $this->orderObserverHandler->expects($this->never())->method('execute');
        $this->import->execute($this->observer);
    }

    public function testExecutesOrdersObserveHandlerWhenOrderIsBeingUpdated()
    {
        $mockCreateDate = '2021-01-01 00:00:00';
        $mockUpdateDate = '2021-02-03 00:00:00';
        $this->orderMock
            ->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn($mockCreateDate);
        $this->orderMock
            ->expects($this->any())
            ->method('getUpdatedAt')
            ->willReturn($mockUpdateDate);
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo([
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_update'],
                    'type' => 'middleware',
                ]),
                $this->equalTo($this->orderMock),
                $this->equalTo([])
            );
        $this->import->execute($this->observer);
    }

    public function testLogsErrorsToLoggingService()
    {
        $mockCreateDate = '2021-01-01 00:00:00';
        $mockUpdateDate = '2021-02-03 00:00:00';
        $this->orderMock
            ->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn($mockCreateDate);
        $this->orderMock
            ->expects($this->any())
            ->method('getUpdatedAt')
            ->willReturn($mockUpdateDate);
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->logger->expects($this->once())->method('error');
        $this->integration->expects($this->once())->method('logErrorToLoggingService');
        $this->import->execute($this->observer);
    }
}
