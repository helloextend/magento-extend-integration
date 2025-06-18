<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Extend\Integration\Service\Api\MetadataBuilder;
use Magento\Framework\App\ProductMetadataInterface;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use \Magento\Framework\Composer\ComposerInformation;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Oauth\Consumer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class MetadataBuilderTest extends TestCase
{
    private IdentityService $identityService;
    private StoreIntegrationInterface $storeIntegration;
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private ProductMetadataInterface $productMetadata;
    private AccessTokenBuilder $accessTokenBuilder;
    private ComposerInformation $composerInformation;
    private IntegrationServiceInterface $integrationService;
    private OauthServiceInterface $oauthService;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private ObjectManager $objectManager;
    private MetadataBuilder $metadataBuilder;
    private array $magentoStoreIdMocks = [1];
    private string $generatedUUIDMock = 'acff4bd1-889c-431f-908e-24fea292337c';
    private string $magentoVersion = '2.4.2';
    private string $extendAccessToken = 'token';
    private string $consumerKey = 'consumerKey';
    private string $storeUUID = 'abf89f21-d240-477a-a462-14797ec5264e';
    private string $moduleVersion = '1.0.0';

    protected function setUp(): void
    {
        $this->identityService = $this->getMockBuilder(IdentityService::class)
            ->onlyMethods(['generateId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->identityService
            ->expects($this->any())
            ->method('generateId')
            ->willReturn($this->generatedUUIDMock);

        $this->storeIntegration = $this->getMockBuilder(StoreIntegrationInterface::class)
            ->onlyMethods(['getStoreUuid'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeIntegration
            ->expects($this->any())
            ->method('getStoreUuid')
            ->willReturn($this->storeUUID);

        $this->storeIntegrationRepository = $this->getMockBuilder(
            StoreIntegrationRepositoryInterface::class
        )
            ->onlyMethods(['getByStoreIdAndActiveEnvironment', 'getListByIntegration'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeIntegrationRepository
            ->expects($this->any())
            ->method('getByStoreIdAndActiveEnvironment')
            ->willReturnMap([
                [$this->magentoStoreIdMocks[0], $this->storeIntegration],
                [0, $this->storeIntegration],
            ]);

        $this->accessTokenBuilder = $this->getMockBuilder(AccessTokenBuilder::class)
            ->onlyMethods(['getAccessToken'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->accessTokenBuilder
            ->expects($this->any())
            ->method('getAccessToken')
            ->willReturn($this->extendAccessToken);

        $this->composerInformation = $this->getMockBuilder(ComposerInformation::class)
            ->onlyMethods(['getInstalledMagentoPackages'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->composerInformation
            ->expects($this->any())
            ->method('getInstalledMagentoPackages')
            ->willReturn(['helloextend/integration' => ['version' => $this->moduleVersion]]);

        $activeIntegration = 1;

        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->scopeConfig->method('getValue')->willReturn($activeIntegration);

        $this->integrationService = $this->createMock(IntegrationServiceInterface::class);
        $integrationModel = $this->createMock(
            \Magento\Integration\Model\Integration::class
        );
        $this->integrationService->method('get')->with($activeIntegration)->willReturn($integrationModel);

        $mockConsumer = $this->getMockBuilder(Consumer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getKey'])
            ->getMock();
        $mockConsumer
            ->expects($this->once())
            ->method('getKey')
            ->willReturn($this->consumerKey);

        $this->oauthService = $this->createMock(OauthServiceInterface::class);
        $this->oauthService->method('loadConsumer')->willReturn($mockConsumer);

        $this->oauthService
            ->expects($this->any())
            ->method('loadConsumer')
            ->willReturn(['helloextend/integration' => $this->magentoVersion]);

        $this->productMetadata = $this->getMockBuilder(ProductMetadataInterface::class)
            ->onlyMethods(['getVersion'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productMetadata
            ->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->magentoVersion);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->objectManager = new ObjectManager($this);
        $this->metadataBuilder = $this->objectManager->getObject(MetadataBuilder::class, [
            'identityService' => $this->identityService,
            'storeIntegrationRepository' => $this->storeIntegrationRepository,
            'productMetadata' => $this->productMetadata,
            'accessTokenBuilder' => $this->accessTokenBuilder,
            'composerInformation' => $this->composerInformation,
            'oauthService' => $this->oauthService,
            'integrationService' => $this->integrationService,
            'scopeConfig' => $this->scopeConfig,
            'logger' => $this->logger,
        ]);
    }

    public function testExecutesMetadataBuilder(): void
    {
        $topic = 'topic';
        $integrationEndpoint = [
            'path' => '/webhooks/' . $topic,
        ];
        $data = [
            'key' => 'value',
        ];

        $expectedHeaders = [
            'X-Extend-Access-Token' => $this->extendAccessToken,
            'Content-Type' => 'application/json',
            'X-Magento-Version' => $this->magentoVersion,
            'X-Extend-Mage-Consumer-Key' => $this->consumerKey,
            'X-Extend-Mage-Store-UUID' => $this->storeUUID,
            'X-Extend-Mage-Module-Version' => $this->moduleVersion,
        ];
        $expectedBody = [
            'webhook_id' => $this->generatedUUIDMock,
            'topic' => $topic,
            'data' => $data,
        ];

        [$actualHeaders, $actualBody] = $this->metadataBuilder->execute(
            $this->magentoStoreIdMocks,
            $integrationEndpoint,
            $data
        );

        $this->assertEquals(
            $expectedHeaders['X-Extend-Access-Token'],
            $actualHeaders['X-Extend-Access-Token']
        );
        $this->assertEquals($expectedHeaders['Content-Type'], $actualHeaders['Content-Type']);
        $this->assertEquals(
            $expectedHeaders['X-Magento-Version'],
            $actualHeaders['X-Magento-Version']
        );

        $this->assertEquals(
            $expectedHeaders['X-Extend-Mage-Consumer-Key'],
            $actualHeaders['X-Extend-Mage-Consumer-Key']
        );
        $this->assertEquals(
            $expectedHeaders['X-Extend-Mage-Store-UUID'],
            $actualHeaders['X-Extend-Mage-Store-UUID']
        );
        $this->assertEquals(
            $expectedHeaders['X-Extend-Mage-Module-Version'],
            $actualHeaders['X-Extend-Mage-Module-Version']
        );
        $this->assertEquals($expectedBody['webhook_id'], $actualBody['webhook_id']);
        $this->assertEquals($expectedBody['topic'], $actualBody['topic']);
        $this->assertEquals($expectedBody['data'], $actualBody['data']);
    }

    public function testExecuteWithNullInStoreIdsIsHandledGracefully(): void
    {
        $storeIdsWithNull = [$this->magentoStoreIdMocks[0], null, 0];
        $topic = 'topic';
        $integrationEndpoint = [
            'path' => '/webhooks/' . $topic,
        ];
        $data = [
            'key' => 'value',
        ];

        [, $actualBody] = $this->metadataBuilder->execute(
            $storeIdsWithNull,
            $integrationEndpoint,
            $data
        );

        $this->assertArrayHasKey('magento_store_uuids', $actualBody);
        $this->assertCount(2, $actualBody['magento_store_uuids']);
        $this->assertEquals($this->storeUUID, $actualBody['magento_store_uuids'][0]);
    }
}
