<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Api\BatchProductObserverHandler;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\Store;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class BatchProductObserverHandlerTest extends TestCase
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
     * @var BatchProductObserverHandler|MockObject
     */
    private $batchProductObserverHandler;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var array
     */
    private $integrationEndpoint;

    /**
     * @var int[]
     */
    private $productIdsMock = [1, 2];

    /**
     * @var array
     */
    private $metadataMock;

    /**
     * @var MetadataBuilder|MockObject
     */
    private $metadataBuilder;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
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
        $this->metadataBuilder = $this->getMockBuilder(MetadataBuilder::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataMock = [
            [
                'X-Extend-Access-Token' => 'token',
                'Content-Type' => 'application/json',
                'X-Magento-Version' => '2.4.2',
            ],
            [
                'webhook_id' => '937ea8a4-69c9-4133-88a0-c1477a9123d6',
                'webhook_created_at' => time(),
                'topic' => 'products/delete',
                'magento_store_uuids' => ['acff4bd1-889c-431f-908e-24fea292337b'],
                'data' => [],
            ],
        ];

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->onlyMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->batchProductObserverHandler = $this->objectManager->getObject(
            BatchProductObserverHandler::class,
            [
                'logger' => $this->logger,
                'integration' => $this->integration,
                'storeManager' => $this->storeManager,
                'metadataBuilder' => $this->metadataBuilder,
                'productRepository' => $this->productRepository,
            ]
        );
    }

    public function testExecutesIntegrationWithExpectedPayload()
    {
        $this->metadataBuilder
            ->expects($this->any())
            ->method('execute')
            ->willReturn($this->metadataMock);

        $numberOfProducts = count($this->productIdsMock);

        for ($i = 0; $i < $numberOfProducts; $i++) {
            $productMock = $this->getMockBuilder(Product::class)
                ->onlyMethods(['getStoreIds'])
                ->disableOriginalConstructor()
                ->getMock();
            $productMock
                ->expects($this->any())
                ->method('getStoreIds')
                ->willReturn([$i + 1]);
            $this->productRepository
                ->expects($this->at($i))
                ->method('getById')
                ->with($this->productIdsMock[$i])
                ->willReturn($productMock);
        }

        $this->integration->expects($this->exactly($numberOfProducts))->method('execute');

        $this->batchProductObserverHandler->execute(
            $this->integrationEndpoint,
            $this->productIdsMock,
            []
        );
    }

    public function testLogsErrorsToLoggingService()
    {
        $this->metadataBuilder
            ->expects($this->any())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->logger->expects($this->once())->method('error');
        $this->integration->expects($this->once())->method('logErrorToLoggingService');
        $this->store
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->storeManager
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->batchProductObserverHandler->execute(
            $this->integrationEndpoint,
            $this->productIdsMock,
            []
        );
    }
}
