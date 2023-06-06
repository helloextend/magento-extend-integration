<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\BatchProductObserverHandler;
use Extend\Integration\Observer\CatalogProductImportBunchDeleteAfter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CatalogImportExport\Model\Import\Product;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class CatalogProductImportBunchDeleteAfterTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CatalogProductImportBunchDeleteAfter
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
    private $bunchDataArrayMock;

    /**
     * @var array
     */
    private $productDataArrayMocks;

    /**
     * @var Product|MockObject
     */
    private $adapterMock;

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
        $this->batchProductObserverHandler = $this->getMockBuilder(
            BatchProductObserverHandler::class
        )
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->integration = $this->getMockBuilder(Integration::class)
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
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getBunch', 'getAdapter'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->bunchDataArrayMock = [
            1 => [
                'sku' => 'sku1',
            ],
            2 => [
                'sku' => 'sku2',
            ],
        ];
        $this->productDataArrayMocks = [['entity_id' => 1], ['entity_id' => 2]];
        $this->adapterMock = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getNewSku'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->adapterMock
            ->expects($this->at(0))
            ->method('getNewSku')
            ->with('sku1')
            ->willReturn($this->productDataArrayMocks[0]);
        $this->adapterMock
            ->expects($this->at(1))
            ->method('getNewSku')
            ->with('sku2')
            ->willReturn($this->productDataArrayMocks[1]);
        $this->event
            ->expects($this->any())
            ->method('getBunch')
            ->willReturn($this->bunchDataArrayMock);
        $this->event
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturn($this->adapterMock);
        $this->observer = $this->createPartialMock(Observer::class, ['getEvent']);
        $this->observer
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->objectManager = new ObjectManager($this);
        $this->import = $this->objectManager->getObject(
            CatalogProductImportBunchDeleteAfter::class,
            [
                'logger' => $this->logger,
                'batchProductObserverHandler' => $this->batchProductObserverHandler,
                'integration' => $this->integration,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    public function testExecutesProductsBatchObserverHandler()
    {
        $this->batchProductObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo([
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_products_delete'],
                    'type' => 'middleware',
                ]),
                $this->equalTo([1, 2]),
                []
            );
        $this->import->execute($this->observer);
    }

    public function testLogsErrorsToLoggingService()
    {
        $this->batchProductObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->logger->expects($this->once())->method('error');
        $this->integration->expects($this->once())->method('logErrorToLoggingService');
        $this->import->execute($this->observer);
    }
}
