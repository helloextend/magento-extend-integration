<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\ShipmentObserverHandler;
use Extend\Integration\Observer\SalesOrderShipmentTrackSaveAfter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class SalesOrderShipmentTrackSaveAfterTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SalesOrderShipmentTrackSaveAfter
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
     * @var ShipmentObserverHandler|MockObject
     */
    private $shipmentObserverHandler;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var Track|MockObject
     */
    private $trackMock;

    /**
     * @var Shipment|MockObject
     */
    private $shipmentMock;

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
        $this->shipmentObserverHandler = $this->getMockBuilder(ShipmentObserverHandler::class)
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
        $this->shipmentMock = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->trackMock = $this->getMockBuilder(Track::class)
            ->setMethods(['getShipment'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->trackMock
            ->expects($this->any())
            ->method('getShipment')
            ->willReturn($this->shipmentMock);
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getTrack'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->event
            ->expects($this->any())
            ->method('getTrack')
            ->willReturn($this->trackMock);
        $this->observer = $this->createPartialMock(Observer::class, ['getEvent']);
        $this->observer
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->objectManager = new ObjectManager($this);
        $this->import = $this->objectManager->getObject(SalesOrderShipmentTrackSaveAfter::class, [
            'logger' => $this->logger,
            'shipmentObserverHandler' => $this->shipmentObserverHandler,
            'integration' => $this->integration,
            'storeManager' => $this->storeManager,
        ]);
    }

    public function testExecutesShipmentObserver()
    {
        $this->shipmentObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo([
                    'path' =>
                        Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_shipments_update'],
                    'type' => 'middleware',
                ]),
                $this->equalTo($this->shipmentMock),
                $this->equalTo([])
            );
        $this->import->execute($this->observer);
    }

    public function testLogsErrorsToLoggingService()
    {
        $this->shipmentObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->logger->expects($this->once())->method('error');
        $this->integration->expects($this->once())->method('logErrorToLoggingService');
        $this->import->execute($this->observer);
    }
}
