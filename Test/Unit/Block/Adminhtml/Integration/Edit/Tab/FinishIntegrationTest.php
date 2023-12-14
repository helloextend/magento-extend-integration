<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Block\Adminhtml\Integration\Edit\Tab;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Block\Adminhtml\Integration\Edit\Tab\FinishIntegration;
use Extend\Integration\Model\Config\Source\Environment;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Phrase;
use Magento\Integration\Api\IntegrationServiceInterface;
use PHPUnit\Framework\TestCase;

class FinishIntegrationTest extends TestCase
{
	/**
	 * @var FinishIntegration
	 */
	private FinishIntegration $finishIntegration;

	/**
	 * @var \Magento\Backend\Block\Template\Context|\PHPUnit\Framework\MockObject\Stub
	 */
	private $context;

	/**
	 * @var Environment|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $environment;

	/**
	 * @var IntegrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $integrationService;

  /**
	 * @var AccessTokenBuilder|(AccessTokenBuilder&object&\PHPUnit\Framework\MockObject\MockObject)|(AccessTokenBuilder&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $accessTokenBuilder;

  /**
	 * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
  private ScopeConfigInterface $scopeConfig;

  /**
	 * @var StoreIntegrationRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private StoreIntegrationRepositoryInterface $storeIntegrationRepository;

   /**
	 * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private RequestInterface $request;

	/**
	 * @var array
	 */
	private array $data;

	public function setUp(): void
	{
		$this->context = $this->createMock(\Magento\Backend\Block\Template\Context::class);
		$this->environment = $this->createMock(Environment::class);
		$this->integrationService = $this->createMock(IntegrationServiceInterface::class);
		$this->accessTokenBuilder = $this->createMock(AccessTokenBuilder::class);
    $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
    $this->storeIntegrationRepository = $this->createMock(StoreIntegrationRepositoryInterface::class);
		$this->data = [];

    $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
    $this->context->method('getRequest')->willReturn($this->request);

		$this->finishIntegration = new FinishIntegration(
			$this->context,
			$this->environment,
			$this->integrationService,
			$this->accessTokenBuilder,
      $this->scopeConfig,
      $this->storeIntegrationRepository,
			$this->data
		);
	}

	public function testGetActiveIntegrationStatusForInactiveIntegration()
	{
    $activeIntegration = 1;
    $this->scopeConfig->method('getValue')->willReturn($activeIntegration);
    $integrationModel = $this->createConfiguredMock(\Magento\Integration\Model\Integration::class, [
			'getId' => 1,
      'getStatus' => 0,
		]);
    $this->integrationService->method('get')->with($activeIntegration)->willReturn($integrationModel);
    $this->accessTokenBuilder->method('getExtendOAuthClientData')->willReturn(['clientId' => null, 'clientSecret' => null]);
		$this->assertEquals($this->finishIntegration->getActiveIntegrationStatusOnStore(), FinishIntegration::INACTIVE_INTEGRATION);
	}

  public function testGetActiveIntegrationStatusWithCurrentStoreSetToCurrentIntegration()
	{
    $activeIntegration = 1;
    $this->scopeConfig->method('getValue')->willReturn($activeIntegration);
    $integrationModel = $this->createConfiguredMock(\Magento\Integration\Model\Integration::class, [
			'getId' => 1,
      'getStatus' => 1,
		]);
    $this->integrationService->method('get')->with($activeIntegration)->willReturn($integrationModel);
    $this->accessTokenBuilder->method('getExtendOAuthClientData')->willReturn(['clientId' => '89rjh89tyrhug3897y', 'clientSecret' => 'fbhn39ry34rhfsdfi98']);
    $this->storeIntegrationRepository->method('getListByIntegration')->willReturn([1, 4, 5]);
    $this->request->method('getParam')->willReturn(4);
		$this->assertEquals($this->finishIntegration->getActiveIntegrationStatusOnStore(), FinishIntegration::ACTIVE_INTEGRATION_WITH_CURRENT_STORE);
	}

  public function testGetActiveIntegrationStatusWithoutCurrentStoreSetToCurrentIntegration()
	{
    $activeIntegration = 1;
    $this->scopeConfig->method('getValue')->willReturn($activeIntegration);
    $integrationModel = $this->createConfiguredMock(\Magento\Integration\Model\Integration::class, [
			'getId' => 1,
      'getStatus' => 1,
		]);
    $this->integrationService->method('get')->with($activeIntegration)->willReturn($integrationModel);
    $this->accessTokenBuilder->method('getExtendOAuthClientData')->willReturn(['clientId' => '89rjh89tyrhug3897y', 'clientSecret' => 'fbhn39ry34rhfsdfi98']);
    $this->storeIntegrationRepository->method('getListByIntegration')->willReturn([1, 5]);
    $this->request->method('getParam')->willReturn(4);
		$this->assertEquals($this->finishIntegration->getActiveIntegrationStatusOnStore(), FinishIntegration::ACTIVE_INTEGRATION_WITHOUT_CURRENT_STORE);
	}

  public function testGetActiveIntegrationStatusWhenThereIsAGeneralException()
	{
    $activeIntegration = 1;
    $this->scopeConfig->method('getValue')->willReturn($activeIntegration);
    $this->integrationService->method('get')->with($activeIntegration)->will($this->throwException(new \Exception()));
		$this->assertEquals($this->finishIntegration->getActiveIntegrationStatusOnStore(), FinishIntegration::ERROR_SEARCHING_FOR_INTEGRATION);
	}

  public function testGetActiveIntegrationStatusWhenIntegrationDoesntExistBecauseItWasDeleted()
	{
    $activeIntegration = 1;
    $this->scopeConfig->method('getValue')->willReturn($activeIntegration);
    $this->integrationService->method('get')->with($activeIntegration)->will($this->throwException(new IntegrationException(new Phrase('The integration with ID "1" doesn\'t exist.'))));
		$this->assertEquals($this->finishIntegration->getActiveIntegrationStatusOnStore(), FinishIntegration::ERROR_SEARCHING_FOR_DELETED_INTEGRATION);
	}
}
