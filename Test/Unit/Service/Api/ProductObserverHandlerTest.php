<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Api\ProductObserverHandler;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\Store;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class ProductObserverHandlerTest extends TestCase
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
     * @var ProductObserverHandler|MockObject
     */
    private $ProductObserverHandler;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var array
     */
    private $integrationEndpoint;

    /**
     * @var Product|MockObject
     */
    private $product;

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
    private string $productIdMock;

    private string $productSkuMock;

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
        $this->store
            ->expects($this->any())
            ->method('getId')
            ->willReturn($this->magentoStoreIdMock);
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
        $this->productIdMock = 'testProductId';
        $this->productSkuMock = 'testProductSku';
        $this->product = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getId', 'getStoreIds', 'getSku'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->product
            ->expects($this->any())
            ->method('getId')
            ->willReturn($this->productIdMock);
        $this->product
            ->expects($this->any())
            ->method('getStoreIds')
            ->willReturn([$this->magentoStoreIdMock]);
        $this->product
            ->expects($this->any())
            ->method('getSku')
            ->willReturn($this->productSkuMock);
        $this->integration
            ->expects($this->any())
            ->method('execute')
            ->with(
                $this->equalTo($this->integrationEndpoint),
                $this->equalTo(
                    array_merge($this->metadataMock, [
                        'data' => [
                            'product_id' => $this->productIdMock,
                        ],
                    ])
                )
            );
        $this->objectManager = new ObjectManager($this);
        $this->ProductObserverHandler = $this->objectManager->getObject(
            ProductObserverHandler::class,
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
        $this->ProductObserverHandler->execute($this->integrationEndpoint, $this->product, []);
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
        $this->ProductObserverHandler->execute($this->integrationEndpoint, $this->product, []);
    }
}
