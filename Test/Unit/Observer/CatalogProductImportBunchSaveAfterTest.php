<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\BatchProductObserverHandler;
use Extend\Integration\Observer\CatalogProductImportBunchSaveAfter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\CatalogImportExport\Model\Import\Product;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class CatalogProductImportBunchSaveAfterTest extends TestCase
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
     * @var Product|MockObject
     */
    private $adapterMock;

    /**
     * @var array
     */
    private $bunchDataArrayMock  = [
        1 => [
            'sku' => 'sku1',
        ],
        2 => [
            'sku' => 'sku2',
        ],
    ];

    /**
     * @var array
     */
    private $productDataArrayMocks = [['entity_id' => 1], ['entity_id' => 2]];

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
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var BatchProductObserverHandler|MockObject
     */
    private $batchProductObserverHandler;

    /**
     * @var CatalogProductImportBunchSaveAfter
     */
    private $import;

    protected function setUp(): void
    {
        $this->adapterMock = $this->createMock(Product::class);
        $this->adapterMock
            ->method('getNewSku')
            ->willReturn($this->returnValueMap([
                [$this->bunchDataArrayMock[1]['sku'], $this->productDataArrayMocks[0]],
                [$this->bunchDataArrayMock[2]['sku'], $this->productDataArrayMocks[1]],
            ]));
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getBunch', 'getAdapter'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->event
            ->method('getBunch')
            ->willReturn($this->bunchDataArrayMock);
        $this->event
            ->method('getAdapter')
            ->willReturn($this->adapterMock);
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
        $this->batchProductObserverHandler = $this->createMock(BatchProductObserverHandler::class);
        $this->import = new CatalogProductImportBunchSaveAfter(
            $this->logger,
            $this->extendService,
            $this->integration,
            $this->storeManager,
            $this->batchProductObserverHandler
        );
    }

    public function testExecutesProductsBatchObserverHandlerWhenExtendIsEnabled()
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
            ->method('getBunch');
        $this->event
            ->expects($this->once())
            ->method('getAdapter');
        $this->adapterMock
            ->expects($this->exactly(2))
            ->method('getNewSku');
        $this->batchProductObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_products_create'],
                    'type' => 'middleware',
                ],
                [1, 2],
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
        $this->batchProductObserverHandler
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
            ->method('getBunch');
        $this->event
            ->expects($this->once())
            ->method('getAdapter');
        $this->adapterMock
            ->expects($this->exactly(2))
            ->method('getNewSku');
        $this->batchProductObserverHandler
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
