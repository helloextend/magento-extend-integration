<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\ProductObserverHandler;
use Extend\Integration\Observer\CatalogProductSaveEntityAfter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class CatalogProductSaveEntityAfterTest extends TestCase
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
    private $productMock;

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
     * @var ProductObserverHandler|MockObject
     */
    private $productObserverHandler;

    /**
     * @var CatalogProductSaveEntityAfter
     */
    private $import;

    protected function setUp(): void
    {
        $this->productMock = $this->createMock(Product::class);
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getProduct'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->event
            ->method('getProduct')
            ->willReturn($this->productMock);
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
        $this->productObserverHandler = $this->createMock(ProductObserverHandler::class);
        $this->import = new CatalogProductSaveEntityAfter(
            $this->logger,
            $this->extendService,
            $this->integration,
            $this->storeManager,
            $this->productObserverHandler
        );
    }

    public function testExecutesProductsObserverAndSendsProductToCreateEndpointWhenProductIsNewAndExtendIsEnabled()
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
            ->method('getProduct');
        $this->productMock
            ->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(true);
        $this->productObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_products_create'],
                    'type' => 'middleware',
                ],
                $this->productMock,
                []
            );
        $this->import->execute($this->observer);
    }

    public function testExecutesProductsObserverAndSendsProductToCreateEndpointWhenProductIsNotNewAndExtendIsEnabled()
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
            ->method('getProduct');
        $this->productMock
            ->expects($this->once())
            ->method('isObjectNew')
            ->willReturn(false);
        $this->productObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_products_update'],
                    'type' => 'middleware',
                ],
                $this->productMock,
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
        $this->productObserverHandler
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
            ->method('getProduct');
        $this->productObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->logger
            ->expects($this->once())
            ->method('error');
        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService');
        $this->storeManager
            ->expects($this->once())
            ->method('getStore');
        $this->import->execute($this->observer);
    }
}
