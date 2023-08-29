<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Block\Adminhtml\Order\View\Tab;

use Extend\Integration\Plugin\Block\Adminhtml\Order\View\Tab\InfoPlugin;
use Extend\Integration\Service\Extend;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderItemInterface;
use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Service\Api\Integration as ExtendIntegrationService;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\Integration;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\Info;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Exception;

class InfoPluginTest extends TestCase
{
    /**
     * @var string
     */
    private $mockTransactionId = 'order-id';

    /**
     * @var int
     */
    private $mockStoreId = 1;

    /**
     * @var int
     */
    private $mockIntegrationId = 1;

    /**
     * @var string
     */
    private $mockMerchantPortalBaseURL = 'https://test.com/';

    /**
     * @var string
     */
    private $mockIdentityLinkUrl;

    /**
     * @var (OrderItemInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockOrderItem;

    /**
     * @var (Order&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $order;

    /**
     * @var (Info&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subject;

    /**
     * @var (StoreIntegrationInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeIntegration;

    /**
     * @var (StoreIntegrationRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationStoresRepository;

    /**
     * @var (Integration&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integration;

    /**
     * @var (IntegrationServiceInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationService;

    /**
     * @var (LoggerInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var (ExtendIntegrationService&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $extendIntegrationService;

    /**
     * @var InfoPlugin
     */
    private $infoPlugin;


    public function setUp(): void
    {
        $this->mockOrderItem = $this->getMockBuilder(OrderItemInterface::class)
            ->setMethods(['getSku'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods(['getStoreId', 'getIncrementId', 'getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->order
            ->expects($this->any())
            ->method('getStoreId')
            ->willReturn($this->mockStoreId);
        $this->order
            ->expects($this->any())
            ->method('getIncrementId')
            ->willReturn($this->mockTransactionId);
        $this->order
            ->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->mockOrderItem]);
        $this->subject = $this->getMockBuilder(Info::class)
            ->setMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->subject
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->order);
        $this->storeIntegration = $this->getMockBuilder(StoreIntegrationInterface::class)
            ->onlyMethods(['getIntegrationId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeIntegration
            ->expects($this->any())
            ->method('getIntegrationId')
            ->willReturn($this->mockIntegrationId);
        $this->integrationStoresRepository = $this->getMockBuilder(StoreIntegrationRepositoryInterface::class)
            ->onlyMethods(['getByStoreIdAndActiveEnvironment'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->integrationStoresRepository
            ->expects($this->any())
            ->method('getByStoreIdAndActiveEnvironment')
            ->with($this->mockStoreId)
            ->willReturn($this->storeIntegration);
        $this->integration = $this->getMockBuilder(Integration::class)
            ->onlyMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockIdentityLinkUrl = $this->mockMerchantPortalBaseURL . 'magento';
        $this->integration
            ->expects($this->any())
            ->method('getData')
            ->with(Integration::IDENTITY_LINK_URL)
            ->willReturn($this->mockIdentityLinkUrl);
        $this->integrationService = $this->getMockBuilder(IntegrationServiceInterface::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->integrationService
            ->expects($this->any())
            ->method('get')
            ->with($this->mockIntegrationId)
            ->willReturn($this->integration);
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendIntegrationService= $this->getMockBuilder(ExtendIntegrationService::class)
            ->setMethods(['logErrorToLoggingService'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->infoPlugin = new InfoPlugin(
            $this->integrationStoresRepository,
            $this->integrationService,
            $this->logger,
            $this->extendIntegrationService
        );
    }

    public function testAfterGetItemsHtmlAppliesLinkToContractsSearchForOrderWhenOrderContainsExtendPlans()
    {
        $this->mockOrderItem
            ->expects($this->any())
            ->method('getSku')
            ->willReturn(Extend::WARRANTY_PRODUCT_SKU);

        $postMethodHTML = $this->infoPlugin->afterGetItemsHtml($this->subject, '');

        $this->assertStringContainsString(
            'View Contract(s) in Extend',
            $postMethodHTML
        );

        $this->assertStringContainsString(
            $this->mockMerchantPortalBaseURL,
            $postMethodHTML
        );

        $this->assertStringNotContainsString(
            'magento',
            $postMethodHTML,
        );

        $this->assertStringContainsString(
            $this->mockTransactionId,
            $postMethodHTML
        );
    }

    public function testAfterGetItemsHtmlDoesNotApplyLinkToContractsSearchForOrderWhenOrderDoesNotContainExtendPlans()
    {
        $this->mockOrderItem
            ->expects($this->any())
            ->method('getSku')
            ->willReturn('sku');

        $preMethodHTML = '';

        $postMethodHTML = $this->infoPlugin->afterGetItemsHtml($this->subject, $preMethodHTML);

        $this->assertEquals($preMethodHTML, $postMethodHTML);
    }

    public function testAfterGetItemsHtmlDoesNotApplyLinkToContractsSearchForOrderWhenExceptionIsThrownWhileFetchingDataForLink()
    {
        $this->mockOrderItem
            ->expects($this->any())
            ->method('getSku')
            ->willReturn(Extend::WARRANTY_PRODUCT_SKU);

        $this->integrationStoresRepository
            ->expects($this->any())
            ->method('getByStoreIdAndActiveEnvironment')
            ->with($this->mockStoreId)
            ->willThrowException(new Exception());
        $this->logger->expects($this->once())->method('warning');
        $this->integration->expects($this->once())->method('logErrorToLoggingService');

        $preMethodHTML = '';

        $postMethodHTML = $this->infoPlugin->afterGetItemsHtml($this->subject, $preMethodHTML);

        $this->assertEquals($preMethodHTML, $postMethodHTML);
    }
}
