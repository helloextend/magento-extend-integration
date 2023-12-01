<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Block\Adminhtml\Integration\Edit\Tab;

use Extend\Integration\Block\Adminhtml\Integration\Edit\Tab\HowToActivate;
use Extend\Integration\Model\Config\Source\Environment;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use Magento\Integration\Api\IntegrationServiceInterface;
use PHPUnit\Framework\TestCase;

class HowToActivateTest extends TestCase
{
	/**
	 * @var HowToActivate
	 */
	private HowToActivate $howToActivate;

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
	 * @var array
	 */
	private array $data;

	/**
	 * @var array|array[]
	 */
	private array $environmentOptionArrayData;

	/**
	 * @var array|array[]
	 */
	private array $activationStatusData;

	/**
	 * @var \Magento\Integration\Model\Integration|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $integrationModel1;

	/**
	 * @var \Magento\Integration\Model\Integration|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $integrationModel2;
	/**
	 * @var AccessTokenBuilder|(AccessTokenBuilder&object&\PHPUnit\Framework\MockObject\MockObject)|(AccessTokenBuilder&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $accessTokenBuilder;

	public function setUp(): void
	{
		$this->context = $this->createStub(\Magento\Backend\Block\Template\Context::class);
		$this->environment = $this->createMock(Environment::class);
		$this->integrationService = $this->createMock(IntegrationServiceInterface::class);
		$this->accessTokenBuilder = $this->createMock(AccessTokenBuilder::class);
		$this->data = [];

		$this->howToActivate = new HowToActivate(
			$this->context,
			$this->environment,
			$this->integrationService,
			$this->accessTokenBuilder,
			$this->data
		);

		$this->environmentOptionArrayData  = [
			['value' => 1, 'label' => 'Extend Integration - Prod'],
			['value' => 2, 'label' => 'Extend Integration - Demo'],
		];
		
		$this->integrationModel1 = $this->createConfiguredMock(\Magento\Integration\Model\Integration::class, [
			'getId' => 1,
			'getStatus' => 1
		]);

		$this->integrationModel2 = $this->createConfiguredMock(\Magento\Integration\Model\Integration::class, [
			'getId' => 2,
			'getStatus' => 0
		]);

		$this->activationStatusData = [
			['integration_id' => 1, 'activation_status' => 1],
			['integration_id' => 2, 'activation_status' => 0]
		];
	}

	public function testGetIntegrationsIfNoIntegrations()
	{
		$this->environment->expects($this->once())->method('toOptionArray')->willReturn([]);
		$this->assertEquals($this->howToActivate->getIntegrations(), []);
	}

	public function testGetIntegrations()
	{
		$this->environment->expects($this->once())->method('toOptionArray')->willReturn($this->environmentOptionArrayData);

		$this->integrationService->expects($this->exactly(2))->method('get')
			->willReturnOnConsecutiveCalls($this->integrationModel1, $this->integrationModel2);

		$this->accessTokenBuilder->expects(($this->exactly(2)))
			->method('getExtendOAuthClientData')
			->willReturnOnConsecutiveCalls(['clientId' => '89rjh89tyrhug3897y', 'clientSecret' => 'fbhn39ry34rhfsdfi98'], ['clientId' => null, 'clientSecret' => null]);

		$this->assertEquals($this->howToActivate->getIntegrations(), $this->activationStatusData);
	}
}