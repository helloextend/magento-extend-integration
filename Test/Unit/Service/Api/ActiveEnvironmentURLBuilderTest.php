<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\Integration;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;

class ActiveEnvironmentURLBuilderTest extends TestCase
{
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private IntegrationServiceInterface $integrationService;
    private string $integrationId = 'integration-id';
    private string $lowerEnvironmentIntegrationBaseURL = 'https://integ-mage-dev.extend.com';
    private string $lowerEnvIntegrationEndpoint;
    private string $lowerEnvApiEndpoint = 'https://api-dev.helloextend.com';
    private string $prodIntegrationBaseURL = 'https://integ-mage.extend.com';
    private string $prodIntegrationEndpoint;
    private string $prodApiEndpoint = 'https://api.helloextend.com';
    private ObjectManager $objectManager;
    private ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder;

    protected function setUp(): void
    {
        $this->lowerEnvIntegrationEndpoint =
            $this->lowerEnvironmentIntegrationBaseURL . '/auth/start';
        $this->prodIntegrationEndpoint = $this->prodIntegrationBaseURL . '/auth/start';
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->with(ActiveEnvironmentURLBuilder::INTEGRATION_ENVIRONMENT_CONFIG)
            ->willReturn($this->integrationId);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->integration = $this->getMockBuilder(Integration::class)
            ->addMethods(['getEndpoint'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->integrationService = $this->getMockBuilder(IntegrationServiceInterface::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->integrationService
            ->expects($this->any())
            ->method('get')
            ->with($this->integrationId)
            ->willReturn($this->integration);

        $this->objectManager = new ObjectManager($this);
        $this->activeEnvironmentURLBuilder = $this->objectManager->getObject(
            ActiveEnvironmentURLBuilder::class,
            [
                'scopeConfig' => $this->scopeConfig,
                'logger' => $this->logger,
                'integrationService' => $this->integrationService,
            ]
        );
    }

    public function testExecutesGetIntegrationURLForLowerEnvironment(): void
    {
        $this->integration
            ->expects($this->once())
            ->method('getEndpoint')
            ->willReturn($this->lowerEnvIntegrationEndpoint);

        $this->assertEquals(
            $this->lowerEnvironmentIntegrationBaseURL,
            $this->activeEnvironmentURLBuilder->getIntegrationURL()
        );
    }

    public function testExecutesGetIntegrationURLForProd(): void
    {
        $this->integration
            ->expects($this->once())
            ->method('getEndpoint')
            ->willReturn($this->prodIntegrationEndpoint);

        $this->assertEquals(
            $this->prodIntegrationBaseURL,
            $this->activeEnvironmentURLBuilder->getIntegrationURL()
        );
    }

    public function testExecutesGetApiURLForLowerEnvironment(): void
    {
        $this->integration
            ->expects($this->once())
            ->method('getEndpoint')
            ->willReturn($this->lowerEnvIntegrationEndpoint);

        $this->assertEquals(
            $this->lowerEnvApiEndpoint,
            $this->activeEnvironmentURLBuilder->getApiURL()
        );
    }

    public function testExecutesGetApiURLForProd(): void
    {
        $this->integration
            ->expects($this->once())
            ->method('getEndpoint')
            ->willReturn($this->prodIntegrationEndpoint);

        $this->assertEquals(
            $this->prodApiEndpoint,
            $this->activeEnvironmentURLBuilder->getApiURL()
        );
    }
}
