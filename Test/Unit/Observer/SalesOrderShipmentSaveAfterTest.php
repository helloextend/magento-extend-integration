<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\ShipmentObserverHandler;
use Extend\Integration\Observer\SalesOrderShipmentSaveAfter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order\Shipment;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class SalesOrderShipmentSaveAfterTest extends TestCase
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
     * @var Shipment|MockObject
     */
    private $shipment;

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
     * @var ShipmentObserverHandler|MockObject
     */
    private $shipmentObserverHandler;

    /**
     * @var SalesOrderShipmentSaveAfter
     */
    private $import;

    protected function setUp(): void
    {
        $this->shipment = $this->createMock(Shipment::class);
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getShipment'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->event
            ->method('getShipment')
            ->willReturn($this->shipment);
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
        $this->shipmentObserverHandler = $this->createMock(ShipmentObserverHandler::class);
        $this->import = new SalesOrderShipmentSaveAfter(
            $this->logger,
            $this->extendService,
            $this->integration,
            $this->storeManager,
            $this->shipmentObserverHandler
        );
    }

    public function testExecutesShipmentObserverAndCallsShipmentCreateWebhookWhenExtendIsEnabledAndShipmentIsNew()
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
            ->method('getShipment');
        $this->shipment
            ->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($this->mockCreateDate);
        $this->shipment
            ->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($this->mockCreateDate);
        $this->shipmentObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' =>
                        Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_shipments_create'],
                    'type' => 'middleware',
                ],
                $this->shipment,
                []
            );
        $this->import->execute($this->observer);
    }

    public function testExecutesShipmentObserverAndCallsShipmentUpdateWebhookWhenExtendIsEnabledAndShipmentIsExisting()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->event
            ->expects($this->once())
            ->method('getShipment')
            ->willReturn($this->shipment);
        $this->shipment
            ->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($this->mockCreateDate);
        $this->shipment
            ->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($this->mockUpdateDate);
        $this->shipmentObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' =>
                        Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_shipments_update'],
                    'type' => 'middleware',
                ],
                $this->shipment,
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
        $this->shipmentObserverHandler
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
            ->method('getShipment');
        $this->shipment
            ->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($this->mockCreateDate);
        $this->shipment
            ->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($this->mockUpdateDate);
        $this->shipmentObserverHandler
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
