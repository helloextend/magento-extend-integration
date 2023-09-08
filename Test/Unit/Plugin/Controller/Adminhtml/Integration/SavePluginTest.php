<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Controller\Adminhtml\Integration;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Model\ResourceModel\StoreIntegration;
use Extend\Integration\Model\ResourceModel\StoreIntegration\Collection;
use Extend\Integration\Model\ResourceModel\StoreIntegration\CollectionFactory;
use Extend\Integration\Model\StoreIntegration as StoreIntegrationInterface;
use Extend\Integration\Service\Api\Integration as IntegrationService;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Extend;
use Extend\Integration\Plugin\Controller\Adminhtml\Integration\SavePlugin;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Integration\Model\Integration as IntegrationModel;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\Oauth\Consumer;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Controller\Adminhtml\Integration;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Integration\Controller\Adminhtml\Integration\Save;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class SavePluginTest extends TestCase
{
    /**
     * @var (StoreIntegrationRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockIntegrationStoresRepository;

    /**
     * @var (StoreIntegration&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockStoreIntegrationResource;

    /**
     * @var (StoreIntegrationInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockStoreIntegrationInterface;

    /**
     * @var (Collection&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockStoreIntegrationCollection;

    /**
     * @var (CollectionFactory&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockCollectionFactory;

    /**
     * @var (ManagerInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockMessageManager;

    /**
     * @var (MetadataBuilder&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockMetadataBuilder;

    /**
     * @var (IntegrationService&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockIntegration;

    /**
     * @var (Consumer&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockConsumer;

    /**
     * @var (OauthServiceInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockOauthService;

    /**
     * @var (IntegrationModel&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockIntegrationModel;

    /**
     * @var (IntegrationServiceInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockIntegrationService;

    /**
     * @var (ScopeConfigInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockScopeConfig;

    /**
     * @var (Store&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockStore;

    /**
     * @var (StoreManagerInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockStoreManager;

    /**
     * @var (Extend&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockExtend;

    /**
     * @var int
     */
    private $mockIntegrationId = 1;

    /**
     * @var int
     */
    private $mockIntegrationStoreId = 1;

    /**
     * @var array
     */
    private $mockIntegrationStoresIds;

    /**
     * @var string
     */
    private $mockCounsumerId = 'consumer_id';

    /**
     * @var array
     */
    private $mockPostData;

    /**
     * @var (RequestInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockRequest;

    /**
     * @var (ResponseInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockResponse;

    /**
     * @var (Save&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockSubject;

    /**
     * @var (LoggerInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockLogger;

    /**
     * @var SavePlugin
     */
    private $plugin;

    public function setUp(): void
    {
        $this->mockExtend = $this->getMockBuilder(Extend::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isEnabled'])
            ->getMock();
        $this->mockIntegrationStoresIds = [$this->mockIntegrationStoreId];
        $this->mockPostData = [
          'integration_stores' => $this->mockIntegrationStoresIds,
        ];
        $this->mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue', 'getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->setMethods(['setRedirect'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockSubject = $this->getMockBuilder(Save::class)
            ->onlyMethods(['getRequest', 'getResponse', 'getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockIntegrationModel = $this->getMockBuilder(IntegrationModel::class)
            ->onlyMethods(['getStatus'])
            ->addMethods(['getSetupType', 'getConsumerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockIntegrationService = $this->getMockBuilder(IntegrationServiceInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMockForAbstractClass();
        $this->mockIntegrationStoresRepository = $this->getMockBuilder(StoreIntegrationRepositoryInterface::class)
            ->onlyMethods(['saveStoreToIntegration', 'getByStoreIdAndIntegrationId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockMessageManager = $this->getMockBuilder(ManagerInterface::class)
            ->onlyMethods(['addSuccessMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockStoreIntegrationInterface = $this->getMockBuilder(StoreIntegrationInterface::class)
            ->onlyMethods(['setDisabled', 'getStoreUuid', 'getExtendStoreUuid' ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mockStoreIntegrationCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'load', 'getItems'])
            ->getMock();
        $this->mockCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->mockStoreIntegrationResource = $this->getMockBuilder(StoreIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $this->mockConsumer = $this->getMockBuilder(Consumer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getKey', 'getSecret'])
            ->getMock();
        $this->mockOauthService = $this->getMockBuilder(OauthServiceInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadConsumer'])
            ->getMockForAbstractClass();
        $this->mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getWebsiteId'])
            ->getMock();
        $this->mockStoreManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMockForAbstractClass();
        $this->mockMetadataBuilder = $this->getMockBuilder(MetadataBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $this->mockIntegration = $this->getMockBuilder(IntegrationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $this->plugin = new SavePlugin(
            $this->mockIntegrationStoresRepository,
            $this->mockStoreIntegrationResource,
            $this->mockCollectionFactory,
            $this->mockMessageManager,
            $this->mockMetadataBuilder,
            $this->mockIntegration,
            $this->mockOauthService,
            $this->mockIntegrationService,
            $this->mockScopeConfig,
            $this->mockStoreManager,
            $this->mockExtend,
            $this->mockLogger
        );
    }

    public function testAroundExecuteSavesAllStoreAssociationsToMagentoAndSendsStoreCreateWebhookRequestsToExtendAndTriggersCallbackWhenExtendIsEnabledAndIntegrationIsActiveAndIntegrationSetupTypeIsZero()
    {
        /**
         * we setup Extend to be enabled
         */
        $this->mockExtend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->mockRequest
            ->expects($this->once())
            ->method('getPostValue')
            ->willReturn($this->mockPostData);

        $this->mockRequest
            ->expects($this->once())
            ->method('getParam')
            ->with(Integration::PARAM_INTEGRATION_ID)
            ->willReturn($this->mockIntegrationId);

        $this->mockSubject
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->mockRequest);

        $this->mockSubject
            ->expects($this->never())
            ->method('getResponse')
            ->willReturn($this->mockResponse);

        $this->mockSubject
            ->expects($this->never())
            ->method('getUrl');

        /**
         * we setup the integration to be active (have a non-zero status)
         */
        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(1);

        /**
         * we setup the integration to have a setup type of zero
         */
        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getSetupType')
            ->willReturn(0);

        $this->mockIntegrationService
            ->expects($this->exactly(1 + count($this->mockIntegrationStoresIds)))
            ->method('get')
            ->with($this->mockIntegrationId)
            ->willReturn($this->mockIntegrationModel);

        $this->mockIntegrationStoresRepository
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('saveStoreToIntegration')
            ->with($this->mockIntegrationId, $this->mockIntegrationStoreId);

        $this->mockMessageManager
            ->expects($this->once())
            ->method('addSuccessMessage');

        // disableAllStoreAssociations() functionality
        $this->mockCollectionFactory
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('create')
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('addFieldToFilter')
            ->with(StoreIntegrationInterface::INTEGRATION_ID, $this->mockIntegrationStoreId)
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('load')
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getItems')
            ->willReturn([$this->mockStoreIntegrationInterface]);

        $this->mockStoreIntegrationInterface
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('setDisabled')
            ->with(1);

        $this->mockStoreIntegrationResource
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('save')
            ->with($this->mockStoreIntegrationInterface);
        // disableAllStoreAssociations() functionality

        // sendIntegrationToExtend() functionality
        $this->mockIntegrationStoresRepository
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getByStoreIdAndIntegrationId')
            ->with($this->mockIntegrationStoreId, $this->mockIntegrationId)
            ->willReturn($this->mockStoreIntegrationInterface);

        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getConsumerId')
            ->willReturn($this->mockCounsumerId);

        $this->mockConsumer
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getKey')
            ->willReturn('key');

        $this->mockConsumer
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getSecret')
            ->willReturn('secret');

        $this->mockOauthService
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('loadConsumer')
            ->with($this->mockCounsumerId)
            ->willReturn($this->mockConsumer);

        $this->mockStoreIntegrationInterface
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getStoreUuid')
            ->willReturn('store_uuid');

        $this->mockStoreIntegrationInterface
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getExtendStoreUuid')
            ->willReturn('extend_store_uuid');

        $this->mockStore
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getName')
            ->willReturn('name');

        $this->mockStore
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getWebsiteId')
            ->willReturn('website_id');

        $this->mockStoreManager
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getStore')
            ->willReturn($this->mockStore);

        $this->mockScopeConfig
            ->expects($this->exactly(2 * count($this->mockIntegrationStoresIds)))
            ->method('getValue')
            ->willReturn('some_value');

        $this->mockMetadataBuilder
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('execute')
            ->willReturn([[], []]);

        $this->mockIntegration
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('execute');
        // sendIntegrationToExtend() functionality

        /**
         * to ensure the $proceed callback is executed we make the callback throw an exception and expect the logger to log it
         */
        $exceptionMessage = 'exception_message';

        $this->mockLogger
            ->expects($this->once())
            ->method('error')
            ->with('An error occurred while saving an integration: ' . $exceptionMessage);

        $this->expectExceptionMessage($exceptionMessage);

        $this->plugin->aroundExecute($this->mockSubject, function () {
            throw new Exception('exception_message');
        });
    }

    public function testAroundExecuteSavesAllStoreAssociationsToMagentoAndDoesNotSendStoreCreateWebhookRequestsToExtendAndTriggersCallbackWhenExtendIsEnabledAndIntegrationIsNotActiveAndIntegrationSetupTypeIsZero()
    {
        /**
         * we setup Extend to be enabled
         */
        $this->mockExtend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->mockRequest
            ->expects($this->once())
            ->method('getPostValue')
            ->willReturn($this->mockPostData);

        $this->mockRequest
            ->expects($this->once())
            ->method('getParam')
            ->with(Integration::PARAM_INTEGRATION_ID)
            ->willReturn($this->mockIntegrationId);

        $this->mockSubject
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->mockRequest);

        $this->mockSubject
            ->expects($this->never())
            ->method('getResponse')
            ->willReturn($this->mockResponse);

        $this->mockSubject
            ->expects($this->never())
            ->method('getUrl');

        /**
         * we setup the integration to not be active (have a zero status)
         */
        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(0);

        /**
         * we setup the integration to have a setup type of zero
         */
        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getSetupType')
            ->willReturn(0);

        $this->mockIntegrationService
            ->expects($this->once())
            ->method('get')
            ->with($this->mockIntegrationId)
            ->willReturn($this->mockIntegrationModel);

        $this->mockIntegrationStoresRepository
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('saveStoreToIntegration')
            ->with($this->mockIntegrationId, $this->mockIntegrationStoreId);

        $this->mockMessageManager
            ->expects($this->once())
            ->method('addSuccessMessage');

        // disableAllStoreAssociations() functionality
        $this->mockCollectionFactory
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('create')
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('addFieldToFilter')
            ->with(StoreIntegrationInterface::INTEGRATION_ID, $this->mockIntegrationStoreId)
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('load')
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getItems')
            ->willReturn([$this->mockStoreIntegrationInterface]);

        $this->mockStoreIntegrationInterface
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('setDisabled')
            ->with(1);

        $this->mockStoreIntegrationResource
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('save')
            ->with($this->mockStoreIntegrationInterface);
        // disableAllStoreAssociations() functionality

        /**
         * this is the first thing called in sendIntegrationToExtend() so we assert it's never called
         */
        $this->mockIntegrationStoresRepository
            ->expects($this->never())
            ->method('getByStoreIdAndIntegrationId');

        /**
         * to ensure the $proceed callback is executed we make the callback throw an exception and expect the logger to log it
         */
        $exceptionMessage = 'exception_message';

        $this->mockLogger
            ->expects($this->once())
            ->method('error')
            ->with('An error occurred while saving an integration: ' . $exceptionMessage);

        $this->expectExceptionMessage($exceptionMessage);

        $this->plugin->aroundExecute($this->mockSubject, function () {
            throw new Exception('exception_message');
        });
    }

    public function testAroundExecuteSavesAllStoreAssociationsToMagentoAndSendsStoreCreateWebhookRequestToExtendAndRedirectsURLWhenExtendIsEnabledAndIntegrationIsActiveAndIntegrationSetupTypeIsNotZero()
    {
        /**
         * we setup Extend to be enabled
         */
        $this->mockExtend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->mockRequest
            ->expects($this->once())
            ->method('getPostValue')
            ->willReturn($this->mockPostData);

        $this->mockRequest
            ->expects($this->once())
            ->method('getParam')
            ->with(Integration::PARAM_INTEGRATION_ID)
            ->willReturn($this->mockIntegrationId);

        $this->mockSubject
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->mockRequest);

        /**
         * we setup the integration to be active (have a non-zero status)
         */
        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(1);

        /**
         * we setup the integration to not have a setup type of zero
         */
        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getSetupType')
            ->willReturn(1);

        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getConsumerId')
            ->willReturn($this->mockCounsumerId);

        $this->mockIntegrationService
            ->expects($this->exactly(1 + count($this->mockIntegrationStoresIds)))
            ->method('get')
            ->with($this->mockIntegrationId)
            ->willReturn($this->mockIntegrationModel);

        $this->mockIntegrationStoresRepository
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('saveStoreToIntegration')
            ->with($this->mockIntegrationId, $this->mockIntegrationStoreId);

        $this->mockMessageManager
            ->expects($this->once())
            ->method('addSuccessMessage');

        $this->mockResponse
            ->expects($this->once())
            ->method('setRedirect');

        $this->mockSubject
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->mockResponse);

        $this->mockSubject
            ->expects($this->once())
            ->method('getUrl')
            ->willReturn('url');


        // disableAllStoreAssociations() functionality
        $this->mockCollectionFactory
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('create')
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('addFieldToFilter')
            ->with(StoreIntegrationInterface::INTEGRATION_ID, $this->mockIntegrationStoreId)
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('load')
            ->willReturn($this->mockStoreIntegrationCollection);

        $this->mockStoreIntegrationCollection
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getItems')
            ->willReturn([$this->mockStoreIntegrationInterface]);

        $this->mockStoreIntegrationInterface
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('setDisabled')
            ->with(1);

        $this->mockStoreIntegrationResource
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('save')
            ->with($this->mockStoreIntegrationInterface);
        // disableAllStoreAssociations() functionality

        // sendIntegrationToExtend() functionality
        $this->mockIntegrationStoresRepository
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getByStoreIdAndIntegrationId')
            ->with($this->mockIntegrationStoreId, $this->mockIntegrationId)
            ->willReturn($this->mockStoreIntegrationInterface);

        $this->mockConsumer
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getKey')
            ->willReturn('key');

        $this->mockConsumer
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getSecret')
            ->willReturn('secret');

        $this->mockOauthService
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('loadConsumer')
            ->with($this->mockCounsumerId)
            ->willReturn($this->mockConsumer);

        $this->mockStoreIntegrationInterface
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getStoreUuid')
            ->willReturn('store_uuid');

        $this->mockStoreIntegrationInterface
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getExtendStoreUuid')
            ->willReturn('extend_store_uuid');

        $this->mockStore
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getName')
            ->willReturn('name');

        $this->mockStore
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getWebsiteId')
            ->willReturn('website_id');

        $this->mockStoreManager
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('getStore')
            ->willReturn($this->mockStore);

        $this->mockScopeConfig
            ->expects($this->exactly(2 * count($this->mockIntegrationStoresIds)))
            ->method('getValue')
            ->willReturn('some_value');

        $this->mockMetadataBuilder
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('execute')
            ->willReturn([[], []]);

        $this->mockIntegration
            ->expects($this->exactly(count($this->mockIntegrationStoresIds)))
            ->method('execute');
        // sendIntegrationToExtend() functionality

        /**
         * to ensure the $proceed callback isn't executed we make the callback throw an exception but don't expect an exception
         */
        $this->plugin->aroundExecute($this->mockSubject, function () {
            throw new Exception('exception_message');
        });
    }

    public function testAroundExecuteImmediatelyTriggersCallbackWhenExtendIsNotEnabled()
    {
        /**
         * we setup Extend to not be enabled
         */
        $this->mockExtend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        /**
         * this is the first thing called after the enabled check so we assert it's never called
         */
        $this->mockSubject
            ->expects($this->never())
            ->method('getRequest');

        /**
         * to ensure the $proceed callback is executed we make the callback throw an exception and expect the logger to log it
         */
        $exceptionMessage = 'exception_message';

        $this->mockLogger
            ->expects($this->once())
            ->method('error')
            ->with('An error occurred while saving an integration: ' . $exceptionMessage);

        $this->expectExceptionMessage($exceptionMessage);

        $this->plugin->aroundExecute($this->mockSubject, function () {
            throw new Exception('exception_message');
        });
    }

    public function testAroundExecuteImmediatelyTriggersCallbackWhenExtendIsEnabledAndRequestDoesNotHaveIntegrationId()
    {
        $this->mockExtend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        /**
         * we setup the request to not have integration_id
         */
        $this->mockRequest
            ->expects($this->once())
            ->method('getParam')
            ->with(Integration::PARAM_INTEGRATION_ID)
            ->willReturn(null);

        /**
         * this is the next thing called if the integration id param is present so we assert it's never called
         */
        $this->mockRequest
            ->expects($this->never())
            ->method('getPostValue');

        $this->mockSubject
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->mockRequest);

        /**
         * to ensure the $proceed callback is executed we make the callback throw an exception and expect the logger to log it
         */
        $exceptionMessage = 'exception_message';

        $this->mockLogger
            ->expects($this->once())
            ->method('error')
            ->with('An error occurred while saving an integration: ' . $exceptionMessage);

        $this->expectExceptionMessage($exceptionMessage);

        $this->plugin->aroundExecute($this->mockSubject, function () {
            throw new Exception('exception_message');
        });
    }


    public function testAroundExecuteDisplaysSuccessMessageAndRedirectsWhenExtendIsEnabledAndRequestDoesNotHaveStoresInPayloadAndIntegrationHasASetupTypeOfOne()
    {
        $this->mockExtend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        /**
         * we setup the request to not have integration_stores
         */
        $this->mockRequest
            ->expects($this->once())
            ->method('getPostValue')
            ->willReturn([]);

        $this->mockRequest
            ->expects($this->once())
            ->method('getParam')
            ->with(Integration::PARAM_INTEGRATION_ID)
            ->willReturn($this->mockIntegrationId);

        /**
         * we setup the integration to have a setup type of one
         */
        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getSetupType')
            ->willReturn(1);

        $this->mockIntegrationService
            ->expects($this->once())
            ->method('get')
            ->with($this->mockIntegrationId)
            ->willReturn($this->mockIntegrationModel);

        $this->mockSubject
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->mockRequest);

        $this->mockMessageManager
            ->expects($this->once())
            ->method('addSuccessMessage');

        $this->mockResponse
            ->expects($this->once())
            ->method('setRedirect');

        $this->mockSubject
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->mockResponse);

        $this->mockSubject
            ->expects($this->once())
            ->method('getUrl')
            ->willReturn('url');

        /**
         * disableAllStoreAssociations() is the next thing called if the request has integration_stores
         * and this is the first thing called by it so we assert it's never called
         */
        $this->mockCollectionFactory
            ->expects($this->never())
            ->method('create');

        /**
         * to ensure the $proceed callback isn't executed we make the callback throw an exception but don't expect an exception
         */
        $this->plugin->aroundExecute($this->mockSubject, function () {
            throw new Exception('exception_message');
        });
    }

    public function testAroundExecuteImmediatelyTriggersCallbackWhenExtendIsEnabledAndRequestDoesNotHaveStoresInPayloadAndIntegrationDoesNotHaveASetupTypeOfOne()
    {
        $this->mockExtend
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        /**
         * we setup the request to not have integration_stores
         */
        $this->mockRequest
            ->expects($this->once())
            ->method('getPostValue')
            ->willReturn([]);

        $this->mockRequest
            ->expects($this->once())
            ->method('getParam')
            ->with(Integration::PARAM_INTEGRATION_ID)
            ->willReturn($this->mockIntegrationId);

        /**
         * we setup the integration to not have a setup type of one
         */
        $this->mockIntegrationModel
            ->expects($this->once())
            ->method('getSetupType')
            ->willReturn(0);

        $this->mockIntegrationService
            ->expects($this->once())
            ->method('get')
            ->with($this->mockIntegrationId)
            ->willReturn($this->mockIntegrationModel);

        $this->mockSubject
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->mockRequest);

        /**
         * we don't want to call disableAllStoreAssociations() if there aren't stores in the payload,
         * to assert it's never called we assert the first thing called by it is never called
         */
        $this->mockCollectionFactory
            ->expects($this->never())
            ->method('create');

        /**
         * addSuccessMessage() would be the next thing called if setup type was one so we assert it's never called
         */
        $this->mockMessageManager
            ->expects($this->never())
            ->method('addSuccessMessage');

        /**
         * to ensure the $proceed callback is executed we make the callback throw an exception and expect the logger to log it
         */
        $exceptionMessage = 'exception_message';

        $this->mockLogger
            ->expects($this->once())
            ->method('error')
            ->with('An error occurred while saving an integration: ' . $exceptionMessage);

        $this->expectExceptionMessage($exceptionMessage);

        $this->plugin->aroundExecute($this->mockSubject, function () {
            throw new Exception('exception_message');
        });
    }

    public function testAroundExecuteSwallowsAndLogsExeptionAndTriggersCallbackWhenAnExceptionIsThrown()
    {
        $caughtExceptionMessage = 'caught_exception_message';

        $this->mockExtend
            ->expects($this->once())
            ->method('isEnabled')
            ->willThrowException(new Exception($caughtExceptionMessage));

        /**
         * this is the first thing called after the enabled check so we assert it's never called
         */
        $this->mockSubject
            ->expects($this->never())
            ->method('getRequest');

        /**
         * to ensure the $proceed callback is executed we make the callback throw an exception and expect the logger to log it
         */
        $this->mockLogger
            ->expects($this->once())
            ->method('error')
            ->with('An error occurred while saving an integration: ' . $caughtExceptionMessage);

        $this->expectExceptionMessage('exception_message');

        $this->plugin->aroundExecute($this->mockSubject, function () {
            throw new Exception('exception_message');
        });
    }
}
