<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Api\ShipmentObserverHandler;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\Store;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class ShipmentObserverHandlerTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

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
    private $ShipmentObserverHandler;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var array
     */
    private $integrationEndpoint;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var Shipment|MockObject
     */
    private $shipment;

    /**
     * @var array
     */
    private $metadataMock;

    /**
     * @var MetadataBuilder|MockObject
     */
    private $metadataBuilder;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var string
     */
    private string $magentoStoreIdMock;

    /**
     * @var string
     */
    private string $orderIdMock;

    /**
     * @var string
     */
    private string $shipmentIdMock;

    protected function setUp(): void
    {
        $this->magentoStoreIdMock = 'acff4bd1-889c-431f-908e-24fea292337b';
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->integrationEndpoint = [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_products_delete'],
            'type' => 'middleware',
        ];
        $this->integration = $this->getMockBuilder(Integration::class)
            ->onlyMethods(['execute', 'logErrorToLoggingService'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->store = $this->getMockBuilder(Store::class)
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager
            ->method('getStore')
            ->willReturn($this->store);
        $this->metadataBuilder = $this->getMockBuilder(MetadataBuilder::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = [
            'webhook_id' => '937ea8a4-69c9-4133-88a0-c1477a9123d6',
            'webhook_created_at' => time(),
            'topic' => 'products/delete',
            'magento_store_uuids' => [$this->magentoStoreIdMock],
        ];
        $this->orderIdMock = 'testOrderId';
        $this->order = $this->getMockBuilder(Order::class)
            ->onlyMethods(['getId', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->order
            ->method('getId')
            ->willReturn($this->orderIdMock);
        $this->order
            ->method('getStoreId')
            ->willReturn($this->magentoStoreIdMock);
        $this->shipmentIdMock = 'testShipmentId';
        $this->shipment = $this->getMockBuilder(Shipment::class)
            ->onlyMethods(['getId', 'getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shipment
            ->method('getId')
            ->willReturn($this->shipmentIdMock);
        $this->shipment
            ->method('getOrder')
            ->willReturn($this->order);
        $this->objectManager = new ObjectManager($this);
        $this->ShipmentObserverHandler = $this->objectManager->getObject(
            ShipmentObserverHandler::class,
            [
                'logger' => $this->logger,
                'integration' => $this->integration,
                'storeManager' => $this->storeManager,
                'metadataBuilder' => $this->metadataBuilder,
            ]
        );
    }

    public function testExecutesIntegrationWithExpectedPayload()
    {
        $data = ['shipment_id' => $this->shipmentIdMock, 'order_id' => $this->orderIdMock];
        $body = array_merge($this->metadataMock, ['data' => $data]);
        $headers = ['Content-Type' => 'application/json'];
        $this->metadataBuilder->expects($this->once())
            ->method('execute')
            ->with($this->anything(), $this->integrationEndpoint, $data)
            ->willReturn([$headers, $body]);
        $this->logger->expects($this->never())->method('error');
        $this->integration->expects($this->never())->method('logErrorToLoggingService');
        $this->integration->expects($this->once())
            ->method('execute')
            ->with($this->integrationEndpoint, $body, $headers);
        $this->ShipmentObserverHandler->execute($this->integrationEndpoint, $this->shipment, []);
    }

    public function testLogsErrorsToLoggingService()
    {
        $exception = new Exception('Metadata could not be built');
        $this->metadataBuilder->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('error');
        $this->integration->expects($this->never())->method('execute');
        $this->integration->expects($this->once())
            ->method('logErrorToLoggingService')
            ->with($exception->getMessage(), $this->store->getId(), 'error', $exception);
        $this->ShipmentObserverHandler->execute($this->integrationEndpoint, $this->shipment, []);
    }
}
