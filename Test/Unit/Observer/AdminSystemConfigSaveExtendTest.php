<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Observer\AdminSystemConfigSaveExtend;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Extend as ExtendService;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\Oauth\Consumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AdminSystemConfigSaveExtendTest extends TestCase
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
   * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private RequestInterface $request;

  /**
   * @var AdminSystemConfigSaveExtend
   */
  private $import;

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
   * @var integrationServiceInterface|MockObject
   */
  private $integrationService;

  /**
   * @var ScopeConfigInterface|MockObject
   */
  private $scopeConfig;

  /**
   * @var StoreIntegrationRepositoryInterface|MockObject
   */
  private $storeIntegrationRepository;

  /**
   * @var OauthServiceInterface|MockObject
   */
  private $oauthService;

  /**
   * @var MetadataBuilder|MockObject
   */
  private $metadataBuilder;

  protected function setUp(): void
  {
    $this->request = $this->createMock(RequestInterface::class);
    $this->event = $this->getMockBuilder(Event::class)
      ->addMethods(['getRequest'])
      ->disableOriginalConstructor()
      ->getMock();
    $this->observer = $this->createMock(Observer::class);

    $this->event->method('getRequest')->willReturn($this->request);
    $this->observer->method('getEvent')->willReturn($this->event);

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->extendService = $this->createMock(ExtendService::class);
    $this->integration = $this->createMock(Integration::class);
    $this->storeManager = $this->createMock(StoreManagerInterface::class);
    $this->integrationService = $this->createMock(IntegrationServiceInterface::class);
    $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
    $this->storeIntegrationRepository = $this->createMock(StoreIntegrationRepositoryInterface::class);
    $this->oauthService = $this->createMock(OauthServiceInterface::class);
    $this->metadataBuilder = $this->createMock(MetadataBuilder::class);

    // the base observer checks if this is enabled
    $this->extendService->method('isEnabled')->willReturn(true);

    $this->import = new AdminSystemConfigSaveExtend(
      $this->logger,
      $this->extendService,
      $this->integration,
      $this->storeManager,
      $this->integrationService,
      $this->scopeConfig,
      $this->storeIntegrationRepository,
      $this->oauthService,
      $this->metadataBuilder,
    );
  }

  public function testReturnIfNotExtendSection()
  {
    $this->request->expects($this->once())->method('getParam')->willReturn('general');
    $this->import->execute($this->observer);
  }

  public function testReturnIfActivateCurrentStoreIsFalsey()
  {
    $this->request->expects($this->exactly(2))->method('getParam')->willReturn('extend', null);
    $this->import->execute($this->observer);
  }

  public function testReturnIfCurrentStoreAlreadyActive()
  {
    $activeIntegration = 1;
    $integrationModel = $this->createConfiguredMock(\Magento\Integration\Model\Integration::class, [
      'getId' => 1,
    ]);

    $this->request->expects($this->exactly(4))->method('getParam')->willReturn('extend', 'on', null, '3');
    $this->scopeConfig->method('getValue')->willReturn($activeIntegration);
    $this->integrationService->method('get')->with($activeIntegration)->willReturn($integrationModel);
    $this->storeIntegrationRepository->method('getListByIntegration')->willReturn([1, 3, 5]);
    $this->storeIntegrationRepository->expects($this->never())->method('saveStoreToIntegration');
    $this->import->execute($this->observer);
  }

  public function testSaveStoreToIntegration()
  {
    $activeIntegration = 1;
    $integrationModel = $this->createConfiguredMock(\Magento\Integration\Model\Integration::class, [
      'getId' => 1,
    ]);
    $mockStoreIntegrationInterface = $this->getMockBuilder(StoreIntegrationInterface::class)
      ->onlyMethods(['getStoreUuid', 'getExtendStoreUuid'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $mockConsumer = $this->getMockBuilder(Consumer::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getKey', 'getSecret'])
      ->getMock();
    $mockConsumer
      ->expects($this->once())
      ->method('getKey')
      ->willReturn('key');
    $mockConsumer
      ->expects($this->once())
      ->method('getSecret')
      ->willReturn('secret');

    $mockStore = $this->getMockBuilder(Store::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getName', 'getWebsiteId'])
      ->getMock();

    $mockStore
      ->expects($this->once())
      ->method('getName')
      ->willReturn('name');

    $mockStore
      ->expects($this->once())
      ->method('getWebsiteId')
      ->willReturn('website_id');

    $this->request->expects($this->exactly(4))->method('getParam')->willReturn('extend', 'on', null, '3');
    $this->scopeConfig->method('getValue')->willReturn($activeIntegration);
    $this->integrationService->method('get')->with($activeIntegration)->willReturn($integrationModel);
    $this->storeIntegrationRepository->method('getListByIntegration')->willReturn([1, 5]);
    $this->storeIntegrationRepository->expects($this->once())->method('saveStoreToIntegration')->with(1, 3);
    $this->storeIntegrationRepository->expects($this->once())->method('getByStoreIdAndIntegrationId')->with(3, 1)->willReturn($mockStoreIntegrationInterface);
    $this->oauthService->expects($this->once())->method('loadConsumer')->willReturn($mockConsumer);
    $this->storeManager->expects($this->once())->method('getStore')->willReturn($mockStore);
    $this->metadataBuilder->expects($this->once())->method('execute')->willReturn([[], []]);
    $this->integration->expects($this->once())->method('execute');
    $this->import->execute($this->observer);
  }

  public function testSaveStoreToIntegrationWithError()
  {
    $activeIntegration = 1;
    $integrationModel = $this->createConfiguredMock(\Magento\Integration\Model\Integration::class, [
      'getId' => 1,
    ]);
    $mockStoreIntegrationInterface = $this->getMockBuilder(StoreIntegrationInterface::class)
      ->onlyMethods(['getStoreUuid', 'getExtendStoreUuid'])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $mockConsumer = $this->getMockBuilder(Consumer::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getKey', 'getSecret'])
      ->getMock();
    $mockConsumer
      ->expects($this->once())
      ->method('getKey')
      ->willReturn('key');
    $mockConsumer
      ->expects($this->once())
      ->method('getSecret')
      ->willReturn('secret');

    $mockStore = $this->getMockBuilder(Store::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getName', 'getWebsiteId'])
      ->getMock();

    $mockStore
      ->expects($this->once())
      ->method('getName')
      ->willReturn('name');

    $mockStore
      ->expects($this->once())
      ->method('getWebsiteId')
      ->willReturn('website_id');

    $this->request->expects($this->exactly(4))->method('getParam')->willReturn('extend', 'on', null, '3');
    $this->scopeConfig->method('getValue')->willReturn($activeIntegration);
    $this->integrationService->method('get')->with($activeIntegration)->willReturn($integrationModel);
    $this->storeIntegrationRepository->method('getListByIntegration')->willReturn([1, 5]);
    $this->storeIntegrationRepository->expects($this->once())->method('saveStoreToIntegration')->with(1, 3);
    $this->storeIntegrationRepository->expects($this->once())->method('getByStoreIdAndIntegrationId')->with(3, 1)->willReturn($mockStoreIntegrationInterface);
    $this->storeIntegrationRepository->expects($this->once())->method('setIntegrationErrorForStoreIdAndIntegrationId')->with(3, 1, 'ERROR: integration error 403');
    $this->oauthService->expects($this->once())->method('loadConsumer')->willReturn($mockConsumer);
    $this->storeManager->expects($this->once())->method('getStore')->willReturn($mockStore);
    $this->metadataBuilder->expects($this->once())->method('execute')->willReturn([[], []]);
    $this->integration->expects($this->once())->method('execute')->willReturn('ERROR: integration error 403');
    $this->import->execute($this->observer);
  }
}
