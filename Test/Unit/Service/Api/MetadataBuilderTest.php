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

class MetadataBuilderTest extends TestCase
{
    private IdentityService $identityService;
    private StoreIntegrationInterface $storeIntegration;
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private ProductMetadataInterface $productMetadata;
    private AccessTokenBuilder $accessTokenBuilder;
    private ObjectManager $objectManager;
    private MetadataBuilder $metadataBuilder;
    private array $magentoStoreIdMocks = [1];
    private string $generatedUUIDMock = 'acff4bd1-889c-431f-908e-24fea292337c';
    private string $magentoVersion = '2.4.2';
    private string $extendAccessToken = 'token';

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
        $this->storeIntegrationRepository = $this->getMockBuilder(
            StoreIntegrationRepositoryInterface::class
        )
            ->onlyMethods(['getByStoreIdAndActiveEnvironment', 'getListByIntegration'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeIntegrationRepository
            ->expects($this->any())
            ->method('getByStoreIdAndActiveEnvironment')
            ->with($this->magentoStoreIdMocks[0])
            ->willReturn($this->storeIntegration);

        $this->accessTokenBuilder = $this->getMockBuilder(AccessTokenBuilder::class)
            ->onlyMethods(['getAccessToken'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->accessTokenBuilder
            ->expects($this->any())
            ->method('getAccessToken')
            ->willReturn($this->extendAccessToken);

        $this->productMetadata = $this->getMockBuilder(ProductMetadataInterface::class)
            ->onlyMethods(['getVersion'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productMetadata
            ->expects($this->any())
            ->method('getVersion')
            ->willReturn($this->magentoVersion);

        $this->objectManager = new ObjectManager($this);
        $this->metadataBuilder = $this->objectManager->getObject(MetadataBuilder::class, [
            'identityService' => $this->identityService,
            'storeIntegrationRepository' => $this->storeIntegrationRepository,
            'productMetadata' => $this->productMetadata,
            'accessTokenBuilder' => $this->accessTokenBuilder,
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
        $this->assertEquals($expectedBody['webhook_id'], $actualBody['webhook_id']);
        $this->assertEquals($expectedBody['topic'], $actualBody['topic']);
        $this->assertEquals($expectedBody['data'], $actualBody['data']);
    }
}
