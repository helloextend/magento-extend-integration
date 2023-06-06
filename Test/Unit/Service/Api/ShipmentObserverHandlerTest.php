<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
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
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);
        $this->metadataBuilder = $this->getMockBuilder(MetadataBuilder::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataMock = [
            'webhook_id' => '937ea8a4-69c9-4133-88a0-c1477a9123d6',
            'webhook_created_at' => time(),
            'topic' => 'products/delete',
            'magento_store_uuids' => [$this->magentoStoreIdMock],
        ];
        $this->metadataBuilder
            ->expects($this->any())
            ->method('execute')
            ->willReturn($this->metadataMock);
        $this->orderIdMock = 'testOrderId';
        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods(['getId', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->order
            ->expects($this->any())
            ->method('getId')
            ->willReturn($this->orderIdMock);
        $this->order
            ->expects($this->any())
            ->method('getStoreId')
            ->willReturn($this->magentoStoreIdMock);
        $this->shipmentIdMock = 'testShipmentId';
        $this->shipment = $this->getMockBuilder(Shipment::class)
            ->setMethods(['getId', 'getOrder'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipment
            ->expects($this->any())
            ->method('getId')
            ->willReturn($this->shipmentIdMock);
        $this->shipment
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->order);
        $this->integration
            ->expects($this->any())
            ->method('execute')
            ->with(
                $this->equalTo($this->integrationEndpoint),
                $this->equalTo(
                    array_merge($this->metadataMock, [
                        'data' => [
                            'shipment_id' => $this->shipmentIdMock,
                            'order_id' => $this->orderIdMock,
                            'additional_fields' => [],
                        ],
                    ])
                )
            );
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
        $this->ShipmentObserverHandler->execute($this->integrationEndpoint, $this->shipment, []);
    }

    public function testLogsErrorsToLoggingService()
    {
        $this->integration = $this->getMockBuilder(Integration::class)
            ->onlyMethods(['execute', 'logErrorToLoggingService'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->integration
            ->expects($this->any())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->logger->expects($this->any())->method('error');
        $this->integration->expects($this->any())->method('logErrorToLoggingService');
        $this->ShipmentObserverHandler->execute($this->integrationEndpoint, $this->shipment, []);
    }
}
