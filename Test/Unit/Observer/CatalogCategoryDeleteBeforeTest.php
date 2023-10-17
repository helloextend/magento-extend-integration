<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\CategoryObserverHandler;
use Extend\Integration\Observer\CatalogCategoryDeleteBefore;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class CatalogCategoryDeleteBeforeTest extends TestCase
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
     * @var CategoryInterface|MockObject
     */
    private $category;

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
     * @var CategoryObserverHandler|MockObject
     */
    private $categoryObserverHandler;

    /**
     * @var CatalogCategoryDeleteBefore
     */
    private $import;

    protected function setUp(): void
    {
        $this->category = $this->createStub(CategoryInterface::class);
        $this->event = $this->getMockBuilder(Event::class)
            ->addMethods(['getCategory'])
            ->disableOriginalConstructor()
            ->getMock();
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
        $this->categoryObserverHandler = $this->createMock(CategoryObserverHandler::class);
        $this->import = new CatalogCategoryDeleteBefore(
            $this->logger,
            $this->extendService,
            $this->integration,
            $this->storeManager,
            $this->categoryObserverHandler
        );
    }

    public function testExecutesCategoryObserverHandlerWhenExtendIsEnabled()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent');
        $this->event
            ->method('getCategory')
            ->willReturn($this->category);
        $this->categoryObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_categories_delete'],
                    'type' => 'middleware',
                ],
                $this->category,
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
        $this->categoryObserverHandler
            ->expects($this->never())
            ->method('execute');
        $this->import->execute($this->observer);
    }

    public function testSkipsExecutionIfEventDoesNotHaveCategory()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getEvent');
         $this->event
            ->method('getCategory')
            ->willReturn(null);
        $this->categoryObserverHandler
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
            ->method('getCategory')
            ->willReturn($this->category);
        $this->categoryObserverHandler
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
