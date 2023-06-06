<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Extend\Integration\Service\Api\AccessTokenBuilder;

class AccessTokenBuilderTest extends TestCase
{
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private ScopeConfigInterface $scopeConfig;
    private Curl $curl;
    private EncryptorInterface $encryptor;
    private string $integrationId = '1';
    private string $clientId = 'client_id';
    private string $encryptedClientSecret = 'encrypted_client_secret';
    private string $decryptedClientSecret = 'decrypted_client_secret';
    private int $storeId = 1;
    private StoreIntegrationInterface $storeIntegration;
    private string $activeEnvironmentApiURL = 'https://example.com';
    private string $tokenGrantType = 'client_credentials';
    private string $scope = 'magento:webhook';
    private string $accessToken = 'access_token';
    private ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder;
    private AccessTokenBuilder $accessTokenBuilder;

    protected function setUp(): void
    {
        $this->storeIntegration = $this->getMockBuilder(StoreIntegrationInterface::class)
            ->onlyMethods(['getExtendClientId', 'getExtendClientSecret'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->payload = [
            'grant_type' => $this->tokenGrantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->decryptedClientSecret,
            'scope' => $this->scope,
        ];

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->with(\Extend\Integration\Service\Api\Integration::INTEGRATION_ENVIRONMENT_CONFIG)
            ->willReturn($this->integrationId);

        $this->storeIntegrationRepository = $this->getMockBuilder(
            StoreIntegrationRepositoryInterface::class
        )
            ->onlyMethods(['getListByIntegration', 'getByStoreIdAndIntegrationId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->encryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->onlyMethods(['decrypt'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->encryptor
            ->expects($this->any())
            ->method('decrypt')
            ->with($this->encryptedClientSecret)
            ->willReturn($this->decryptedClientSecret);

        $this->activeEnvironmentURLBuilder = $this->getMockBuilder(
            ActiveEnvironmentURLBuilder::class
        )
            ->onlyMethods(['getApiURL'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->activeEnvironmentURLBuilder
            ->expects($this->any())
            ->method('getApiURL')
            ->willReturn($this->activeEnvironmentApiURL);

        $this->curl = $this->getMockBuilder(Curl::class)
            ->onlyMethods(['post', 'getBody'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->accessTokenBuilder = $this->objectManager->getObject(AccessTokenBuilder::class, [
            'storeIntegrationRepository' => $this->storeIntegrationRepository,
            'scopeConfig' => $this->scopeConfig,
            'curl' => $this->curl,
            'encryptor' => $this->encryptor,
            'activeEnvironmentURLBuilder' => $this->activeEnvironmentURLBuilder,
        ]);
    }

    public function testGetAccessTokenReturnsAccessTokenWhenRepoReturnsValidIntegrationAndAPIResponseComesBackWithAccessToken()
    {
        $this->storeIntegration
            ->expects($this->once())
            ->method('getExtendClientId')
            ->willReturn($this->clientId);
        $this->storeIntegration
            ->expects($this->once())
            ->method('getExtendClientSecret')
            ->willReturn($this->encryptedClientSecret);
        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getListByIntegration')
            ->with((int) $this->integrationId)
            ->willReturn([$this->storeId]);
        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getByStoreIdAndIntegrationId')
            ->with((int) $this->integrationId, $this->storeId)
            ->willReturn($this->storeIntegration);
        $this->curl
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->activeEnvironmentApiURL . AccessTokenBuilder::TOKEN_EXCHANGE_ENDPOINT,
                $this->payload
            );
        $this->curl
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode([
                    'access_token' => $this->accessToken,
                ])
            );
        $this->assertEquals($this->accessToken, $this->accessTokenBuilder->getAccessToken());
    }

    public function testGetAccessTokenReturnsEmptyAccessTokenWhenRepoDoesNotReturnStoreIds()
    {
        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getListByIntegration')
            ->with((int) $this->integrationId)
            ->willReturn([]);
        $this->assertEquals('', $this->accessTokenBuilder->getAccessToken());
    }

    public function testGetAccessTokenReturnsEmptyAccessTokenWhenRepoReturnsIntegrationWithoutClientInfo()
    {
        $this->storeIntegration
            ->expects($this->once())
            ->method('getExtendClientId')
            ->willReturn(null);
        $this->storeIntegration
            ->expects($this->once())
            ->method('getExtendClientSecret')
            ->willReturn(null);
        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getListByIntegration')
            ->with((int) $this->integrationId)
            ->willReturn([$this->storeId]);
        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getByStoreIdAndIntegrationId')
            ->with((int) $this->integrationId, $this->storeId)
            ->willReturn($this->storeIntegration);
        $this->assertEquals('', $this->accessTokenBuilder->getAccessToken());
    }

    public function testGetAccessTokenReturnsEmptyAccessTokenWhenRepoReturnsValidIntegrationAndAPIResponseComesBackWithNoAccessToken()
    {
        $this->storeIntegration
            ->expects($this->once())
            ->method('getExtendClientId')
            ->willReturn($this->clientId);
        $this->storeIntegration
            ->expects($this->once())
            ->method('getExtendClientSecret')
            ->willReturn($this->encryptedClientSecret);
        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getListByIntegration')
            ->with((int) $this->integrationId)
            ->willReturn([$this->storeId]);
        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getByStoreIdAndIntegrationId')
            ->with((int) $this->integrationId, $this->storeId)
            ->willReturn($this->storeIntegration);
        $this->curl->expects($this->once())->method('post');
        $this->curl
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode([
                    'other_property' => 'value',
                ])
            );
        $this->assertEquals('', $this->accessTokenBuilder->getAccessToken());
    }
}
