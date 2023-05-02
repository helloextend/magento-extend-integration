<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class OrderObserverHandlerTest extends TestCase
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
     * @var OrderObserverHandler|MockObject
     */
    private $OrderObserverHandler;

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
    private string $orderStatusMock;

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
        $this->orderIdMock = 'testId';
        $this->orderStatusMock = 'testStatus';
        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods(['getId', 'getStatus'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->order
            ->expects($this->any())
            ->method('getId')
            ->willReturn($this->orderIdMock);
        $this->order
            ->expects($this->any())
            ->method('getStatus')
            ->willReturn($this->orderStatusMock);
        $this->integration
            ->expects($this->any())
            ->method('execute')
            ->with(
                $this->equalTo($this->integrationEndpoint),
                $this->equalTo(
                    array_merge($this->metadataMock, [
                        'data' => [
                            'order_id' => $this->orderIdMock,
                            'order_status' => $this->orderStatusMock,
                            'additional_fields' => [],
                        ],
                    ])
                )
            );
        $this->objectManager = new ObjectManager($this);
        $this->OrderObserverHandler = $this->objectManager->getObject(OrderObserverHandler::class, [
            'logger' => $this->logger,
            'integration' => $this->integration,
            'storeManager' => $this->storeManager,
            'metadataBuilder' => $this->metadataBuilder,
        ]);
    }

    public function testExecutesIntegrationWithExpectedPayload()
    {
        $this->OrderObserverHandler->execute($this->integrationEndpoint, $this->order, []);
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
        $this->OrderObserverHandler->execute($this->integrationEndpoint, $this->order, []);
    }
}
