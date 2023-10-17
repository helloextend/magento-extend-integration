<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Extend\Integration\Service\Api\CategoryObserverHandler;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class CategoryObserverHandlerTest extends TestCase
{
    /**
     * @var int
     */
    private $storeId = 1;

    /**
     * @var Store|MockObject
     */
    private $store;

    /**
     * @var array
     */
    private $integrationEndpointArray = [];

    /**
     * @var int
     */
    private $categoryId = 1;

    /**
     * @var string
     */
    private $categoryName = 'category-name';

    /**
     * @var CategoryInterface|MockObject
     */
    private $category;

    /**
     * @var StoreIntegrationInterface|MockObject
     */
    private $storeIntegration;

    /**
     * @var array
     */
    private $metadataHeaders = [];

    /**
     * @var array
     */
    private $metadataBody = [];

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var MetadataBuilder|MockObject
     */
    private $metadataBuilder;

    /**
     * @var StoreIntegrationRepositoryInterface
     */
    private $storeIntegrationRepository;

    /**
     * @var CategoryObserverHandler
     */
    private $categoryObserverHandler;

    protected function setUp(): void
    {
        $this->category = $this->getMockBuilder(CategoryInterface::class)
            ->addMethods(['getStoreIds'])
            ->onlyMethods(['getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->category
            ->method('getStoreIds')
            ->willReturn([$this->storeId]);
        $this->storeIntegration = $this->createMock(StoreIntegrationInterface::class);
        $this->store = $this->createConfiguredMock(Store::class, [
            'getId' => $this->storeId,
        ]);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->integration = $this->createMock(Integration::class);
        $this->storeManager = $this->createConfiguredMock(StoreManagerInterface::class, [
            'getStore' => $this->store
        ]);
        $this->metadataBuilder = $this->createConfiguredMock(MetadataBuilder::class, [
            'execute' => [
                $this->metadataHeaders,
                $this->metadataBody
            ]
        ]);
        $this->storeIntegrationRepository = $this->createMock(StoreIntegrationRepositoryInterface::class);
        $this->categoryObserverHandler = new CategoryObserverHandler(
            $this->logger,
            $this->integration,
            $this->storeManager,
            $this->metadataBuilder,
            $this->storeIntegrationRepository
        );
    }

    public function testExecutesIntegrationWithExpectedPayload()
    {
         $this->category
            ->expects($this->once())
            ->method('getId')
            ->willReturn($this->categoryId);

        $this->category
            ->expects($this->once())
            ->method('getName')
            ->willReturn($this->categoryName);

        $this->category
            ->expects($this->once())
            ->method('getStoreIds');

        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getByStoreIdAndActiveEnvironment')
            ->with($this->storeId)
            ->willReturn($this->storeIntegration);

        $this->metadataBuilder
            ->expects($this->once())
            ->method('execute')
            ->with([$this->storeId], $this->integrationEndpointArray, ['category_id' => $this->categoryId, 'category_name' => $this->categoryName])
            ->willReturn([$this->metadataHeaders, $this->metadataBody]);

        $this->integration
            ->expects($this->once())
            ->method('execute')
            ->with($this->integrationEndpointArray, $this->metadataHeaders, $this->metadataBody);

        $this->categoryObserverHandler->execute($this->integrationEndpointArray, $this->category, []);
    }

    public function testThrowsAndCatchesExceptionWhenCategoryHasNoId()
    {
        $this->category
            ->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $thrownExceptionMessage = 'The observed category is missing an id, which is required by the Extend Integration Service.';

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Extend Category Observer encountered the following error: ' . $thrownExceptionMessage);

        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService')
            ->with($thrownExceptionMessage, $this->storeId, 'error');

        $this->integration
            ->expects($this->never())
            ->method('execute');

        $this->categoryObserverHandler->execute($this->integrationEndpointArray, $this->category, []);
    }

    public function testThrowsAndCatchesExceptionWhenCategoryHasNoName()
    {
        $this->category
            ->expects($this->once())
            ->method('getId')
            ->willReturn($this->categoryId);

        $this->category
            ->expects($this->once())
            ->method('getName')
            ->willReturn(null);

        $thrownExceptionMessage = 'The observed category is missing a name, which is required by the Extend Integration Service.';

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Extend Category Observer encountered the following error: ' . $thrownExceptionMessage);

        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService')
            ->with($thrownExceptionMessage, $this->storeId, 'error');

        $this->integration
            ->expects($this->never())
            ->method('execute');

        $this->categoryObserverHandler->execute($this->integrationEndpointArray, $this->category, []);
    }

    public function testSwallowsErrorAndLogsWarningWhenCategoryHasStoreThatIsNotIntegrated()
    {
         $this->category
            ->expects($this->once())
            ->method('getId')
            ->willReturn($this->categoryId);

        $this->category
            ->expects($this->once())
            ->method('getName')
            ->willReturn($this->categoryName);

        $this->category
            ->expects($this->once())
            ->method('getStoreIds');

        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getByStoreIdAndActiveEnvironment')
            ->with($this->storeId)
            ->willThrowException(new NoSuchEntityException(__('Integration Not Found')));

        $expectedLogMessage = 'No integration found for store with id: ' . $this->storeId;

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($expectedLogMessage);

        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService')
            ->with($expectedLogMessage, $this->storeId, 'warn');

        // We've set up the only associated store to not be integrated, so we expect the integration call to not be made
        $this->metadataBuilder
            ->expects($this->never())
            ->method('execute');

        $this->integration
            ->expects($this->never())
            ->method('execute');

        $this->categoryObserverHandler->execute($this->integrationEndpointArray, $this->category, []);
    }

    public function testSwallowsErrorAndLogsWarningWhenStoreIntegrationRepositoryMethodThrowsNonNoSuchEntityException()
    {
         $this->category
            ->expects($this->once())
            ->method('getId')
            ->willReturn($this->categoryId);

        $this->category
            ->expects($this->once())
            ->method('getName')
            ->willReturn($this->categoryName);

        $this->category
            ->expects($this->once())
            ->method('getStoreIds');

        $thrownExceptionMessage = 'Whoopsie daisy!';

        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getByStoreIdAndActiveEnvironment')
            ->with($this->storeId)
            ->willThrowException(new Exception($thrownExceptionMessage));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Extend Category Observer encountered the following error: ' . $thrownExceptionMessage);

        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService')
            ->with($thrownExceptionMessage, $this->storeId, 'error');

        $this->metadataBuilder
            ->expects($this->never())
            ->method('execute');

        $this->integration
            ->expects($this->never())
            ->method('execute');

        $this->categoryObserverHandler->execute($this->integrationEndpointArray, $this->category, []);
    }

    public function testLogsErrorsToLoggingService()
    {
        $this->category
            ->expects($this->once())
            ->method('getId')
            ->willReturn($this->categoryId);

        $this->category
            ->expects($this->once())
            ->method('getName')
            ->willReturn($this->categoryName);

        $this->category
            ->expects($this->once())
            ->method('getStoreIds');

        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getByStoreIdAndActiveEnvironment')
            ->with($this->storeId)
            ->willReturn($this->storeIntegration);

        $this->metadataBuilder
            ->expects($this->once())
            ->method('execute')
            ->with([$this->storeId], $this->integrationEndpointArray, ['category_id' => $this->categoryId, 'category_name' => $this->categoryName])
            ->willReturn([$this->metadataHeaders, $this->metadataBody]);

        $thrownExceptionMessage = 'Whoopsie daisy!';

        $this->integration
            ->expects($this->once())
            ->method('execute')
            ->with($this->integrationEndpointArray, $this->metadataHeaders, $this->metadataBody)
            ->willThrowException(new Exception($thrownExceptionMessage));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Extend Category Observer encountered the following error: ' . $thrownExceptionMessage);

        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService')
            ->with($thrownExceptionMessage, $this->storeId, 'error');

        $this->categoryObserverHandler->execute($this->integrationEndpointArray, $this->category, []);
    }
}
