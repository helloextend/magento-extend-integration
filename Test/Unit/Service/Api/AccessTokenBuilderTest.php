<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service\Api;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Extend\Integration\Model\ExtendOAuthClient;
use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Extend\Integration\Api\ExtendOAuthClientRepositoryInterface;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

class AccessTokenBuilderTest extends TestCase
{
    /** @var string */
    private $integrationId = '1';

    /** @var string */
    private $clientId = 'client_id';

    /** @var string */
    private $encryptedClientSecret = 'encrypted_client_secret';

    /** @var string */
    private $decryptedClientSecret = 'decrypted_client_secret';

    /** @var int */
    private $storeId = 1;

    /** @var string */
    private $activeEnvironmentApiURL = 'https://example.com';

    /** @var string */
    private $tokenGrantType = 'client_credentials';

    /** @var string */
    private $scope = 'magento:webhook';

    /** @var string */
    private $accessToken = 'access_token';

    /** @var string */
    private $encryptedAccessToken = 'encrypted_access_token';

    /** @var ExtendOAuthClientRepositoryInterface */
    private $extendOAuthClientRepository;

    /** @var StoreIntegrationRepositoryInterface */
    private $storeIntegrationRepository;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var Curl */
    private $curl;

    /** @var EncryptorInterface */
    private $encryptor;

    /** @var ExtendOAuthClient */
    private $extendOAuthClient;

    /** @var StoreIntegrationInterface */
    private $storeIntegration;

    /** @var ActiveEnvironmentURLBuilder */
    private $activeEnvironmentURLBuilder;

    /** @var AccessTokenBuilder */
    private $accessTokenBuilder;

    /** @var array */
    private $payload;

    /** @var ObjectManager */
    private $objectManager;

    /** @var \Extend\Integration\Model\ResourceModel\ExtendOAuthClient */
    private $extendOAuthClientResource;

    protected function setUp(): void
    {
        $this->extendOAuthClient = $this->getMockBuilder(ExtendOAuthClient::class)
            ->onlyMethods(['setExtendAccessToken', 'getExtendClientId', 'getExtendClientSecret', 'getExtendAccessToken'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

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

        $this->extendOAuthClientRepository = $this->getMockBuilder(ExtendOAuthClientRepositoryInterface::class)
            ->onlyMethods(['getByIntegrationId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();


        $this->storeIntegrationRepository = $this->getMockBuilder(
            StoreIntegrationRepositoryInterface::class
        )
            ->onlyMethods(['getListByIntegration', 'getByStoreIdAndIntegrationId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->encryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->onlyMethods(['decrypt', 'encrypt'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

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

        $this->extendOAuthClientResource = $this->createStub(
            \Extend\Integration\Model\ResourceModel\ExtendOAuthClient::class
        );

        $this->objectManager = new ObjectManager($this);
        $this->accessTokenBuilder = $this->objectManager->getObject(AccessTokenBuilder::class, [
            'extendOAuthClientRepository' => $this->extendOAuthClientRepository,
            'storeIntegrationRepository' => $this->storeIntegrationRepository,
            'scopeConfig' => $this->scopeConfig,
            'curl' => $this->curl,
            'encryptor' => $this->encryptor,
            'activeEnvironmentURLBuilder' => $this->activeEnvironmentURLBuilder,
            'extendOAuthClientResource' => $this->extendOAuthClientResource,
        ]);
    }

    public function testGetAccessTokenReturnsAccessTokenWhenNoExtendOAuthClientDataExistsAndRepoReturnsValidIntegrationAndAPIResponseComesBackWithAccessToken()
    {
        // Mock the ExtendOAuthClientRepository to throw NoSuchEntityException to trigger fallback
        $this->extendOAuthClientRepository
            ->expects($this->exactly(2))
            ->method('getByIntegrationId')
            ->with((int) $this->integrationId)
            ->willThrowException(new NoSuchEntityException())
            ->willReturn($this->extendOAuthClient);

        $this->encryptor
            ->expects($this->once())
            ->method('decrypt')
            ->with($this->encryptedClientSecret)
            ->willReturn($this->decryptedClientSecret);
        $this->encryptor
            ->expects($this->once())
            ->method('encrypt')
            ->with($this->accessToken)
            ->willReturn($this->encryptedAccessToken);

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
        $this->extendOAuthClient->expects($this->once())->method('setExtendAccessToken');
        $this->extendOAuthClientResource
            ->expects($this->once())
            ->method('save')
            ->with($this->extendOAuthClient);
        $this->assertEquals($this->accessToken, $this->accessTokenBuilder->getAccessToken());
    }

    public function testGetAccessTokenReturnsEmptyAccessTokenWhenNoExtendOAuthClientDataExistsAndRepoDoesNotReturnStoreIds()
    {
        // Mock the ExtendOAuthClientRepository to throw NoSuchEntityException to trigger fallback
        $this->extendOAuthClientRepository
            ->expects($this->once())
            ->method('getByIntegrationId')
            ->with((int) $this->integrationId)
            ->willThrowException(new NoSuchEntityException());

        $this->storeIntegrationRepository
            ->expects($this->once())
            ->method('getListByIntegration')
            ->with((int) $this->integrationId)
            ->willReturn([]);
        $this->extendOAuthClient->expects($this->never())->method('setExtendAccessToken');
        $this->assertEquals('', $this->accessTokenBuilder->getAccessToken());
    }

    public function testGetAccessTokenReturnsEmptyAccessTokenWhenNoExtendOAuthClientDataExistsAndRepoReturnsIntegrationWithoutClientInfo()
    {
        // Mock the ExtendOAuthClientRepository to throw NoSuchEntityException to trigger fallback
        $this->extendOAuthClientRepository
            ->expects($this->once())
            ->method('getByIntegrationId')
            ->with((int) $this->integrationId)
            ->willThrowException(new NoSuchEntityException());

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
        $this->extendOAuthClient->expects($this->never())->method('setExtendAccessToken');
        $this->assertEquals('', $this->accessTokenBuilder->getAccessToken());
    }

    public function testGetAccessTokenReturnsEmptyAccessTokenWhenNoExtendOAuthClientDataExistsAndRepoReturnsValidIntegrationAndAPIResponseComesBackWithNoAccessToken()
    {
        // Mock the ExtendOAuthClientRepository to throw NoSuchEntityException to trigger fallback
        $this->extendOAuthClientRepository
            ->expects($this->once())
            ->method('getByIntegrationId')
            ->with((int) $this->integrationId)
            ->willThrowException(new NoSuchEntityException());

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
        $this->extendOAuthClient->expects($this->never())->method('setExtendAccessToken');
        $this->assertEquals('', $this->accessTokenBuilder->getAccessToken());
    }

    public function testGetAccessTokenReturnsAccessToken()
    {
        // Expect that ExtendOAuthClientRepository is called with the integration ID
        // and returns an ExtendOAuthClient instance
        $this->extendOAuthClientRepository
            ->expects($this->exactly(2))
            ->method('getByIntegrationId')
            ->with((int) $this->integrationId)
            ->willThrowException(new NoSuchEntityException())
            ->willReturn($this->extendOAuthClient);

        $this->encryptor
            ->expects($this->once())
            ->method('decrypt')
            ->with($this->encryptedClientSecret)
            ->willReturn($this->decryptedClientSecret);
        $this->encryptor
            ->expects($this->once())
            ->method('encrypt')
            ->with($this->accessToken)
            ->willReturn($this->encryptedAccessToken);

        $this->extendOAuthClient
            ->expects($this->once())
            ->method('setExtendAccessToken');

        // Expect that the ExtendOAuthClient instance is called to get the client_id
        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendClientId')
            ->willReturn($this->clientId);

        // Expect that the ExtendOAuthClient instance is called to get the client_secret
        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendClientSecret')
            ->willReturn($this->encryptedClientSecret);

        // Expect that a POST request is made to the token exchange endpoint
        $this->curl->expects($this->once())->method('post');

        // Mock a successful responds from the token exchange endpoint
        $this->curl
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode([
                    'access_token' => $this->accessToken,
                ])
            );

        $this->extendOAuthClientResource
            ->expects($this->once())
            ->method('save')
            ->with($this->extendOAuthClient);

        // Execute the method under test and assert that the access token is returned
        $this->assertEquals($this->accessToken, $this->accessTokenBuilder->getAccessToken());

        // Expect that storeIntegrationRepository->getListByIntegration was not called
        $this->storeIntegrationRepository->expects($this->never())->method('getListByIntegration');
    }

    public function testReturnsExistingAccessTokenFromRecord()
    {
        $this->extendOAuthClientRepository
            ->expects($this->once())
            ->method('getByIntegrationId')
            ->with((int) $this->integrationId)
            ->willReturn($this->extendOAuthClient);


        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendClientId')
            ->willReturn($this->clientId);
        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendClientSecret')
            ->willReturn($this->encryptedClientSecret);
        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendAccessToken')
            ->willReturn($this->encryptedAccessToken);


        // Expiry is more than a minute from now
        $decryptedAccessToken = 'header.'.base64_encode(json_encode(['exp' => time() + 100])).'.footer';
        $this->encryptor
            ->expects($this->once())
            ->method('decrypt')
            ->with($this->encryptedAccessToken)
            ->willReturn($decryptedAccessToken);

        // Execute the method under test and assert that the access token is returned
        $this->assertEquals($decryptedAccessToken, $this->accessTokenBuilder->getAccessToken());

        $this->curl->expects($this->never())->method('post');
    }

    public function testFetchesNewAccessTokenIfStoredTokenIsWithinOneMinuteOfExpiry()
    {
        $this->extendOAuthClientRepository
            ->expects($this->exactly(2))
            ->method('getByIntegrationId')
            ->with((int) $this->integrationId)
            ->willReturn($this->extendOAuthClient);

        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendClientId')
            ->willReturn($this->clientId);
        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendClientSecret')
            ->willReturn($this->encryptedClientSecret);
        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendAccessToken')
            ->willReturn($this->encryptedAccessToken);

        // Expiry is less than a minute from now
        $decryptedAccessToken = 'header.'.base64_encode(json_encode(['exp' => time() + 30])).'.footer';
        $this->encryptor
            ->expects($this->exactly(2))
            ->method('decrypt')
            ->willReturnOnConsecutiveCalls($decryptedAccessToken, $this->decryptedClientSecret);
        $this->encryptor
            ->expects($this->once())
            ->method('encrypt')
            ->with($this->accessToken)
            ->willReturn($this->encryptedAccessToken);

        // Expect that a POST request is made to the token exchange endpoint
        $this->curl->expects($this->once())->method('post');

        // Mock a successful responds from the token exchange endpoint
        $this->curl
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode([
                    'access_token' => $this->accessToken,
                ])
            );

        $this->extendOAuthClient->expects($this->once())->method('setExtendAccessToken');
        $this->extendOAuthClientResource
            ->expects($this->once())
            ->method('save')
            ->with($this->extendOAuthClient);

        $this->assertEquals($this->accessToken, $this->accessTokenBuilder->getAccessToken());
    }

    public function testFetchesNewAccessTokenIfStoredTokenIsPassedExpiry()
    {
        $this->extendOAuthClientRepository
            ->expects($this->exactly(2))
            ->method('getByIntegrationId')
            ->with((int) $this->integrationId)
            ->willReturn($this->extendOAuthClient);

        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendClientId')
            ->willReturn($this->clientId);
        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendClientSecret')
            ->willReturn($this->encryptedClientSecret);
        $this->extendOAuthClient
            ->expects($this->once())
            ->method('getExtendAccessToken')
            ->willReturn($this->encryptedAccessToken);

        // Expiry is one minute ago
        $decryptedAccessToken = 'header.'.base64_encode(json_encode(['exp' => time() - 60])).'.footer';
        $this->encryptor
            ->expects($this->exactly(2))
            ->method('decrypt')
            ->willReturnOnConsecutiveCalls($decryptedAccessToken, $this->decryptedClientSecret);
        $this->encryptor
            ->expects($this->once())
            ->method('encrypt')
            ->with($this->accessToken)
            ->willReturn($this->encryptedAccessToken);

        // Expect that a POST request is made to the token exchange endpoint
        $this->curl->expects($this->once())->method('post');

        // Mock a successful responds from the token exchange endpoint
        $this->curl
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(
                json_encode([
                    'access_token' => $this->accessToken,
                ])
            );

        $this->extendOAuthClient->expects($this->once())->method('setExtendAccessToken');
        $this->extendOAuthClientResource
            ->expects($this->once())
            ->method('save')
            ->with($this->extendOAuthClient);

        $this->assertEquals($this->accessToken, $this->accessTokenBuilder->getAccessToken());
    }

	public function testGetExtendOAuthClientDataWithNoIntegrationIdProvided()
	{
		$this->scopeConfig
			->expects($this->once())
			->method('getValue');
		$this->extendOAuthClientRepository
			->expects($this->once())
			->method('getByIntegrationId')
			->with((int) $this->integrationId)
			->willReturn($this->extendOAuthClient);
		$this->extendOAuthClient
			->expects($this->once())
			->method('getExtendClientId')
			->willReturn($this->clientId);
		$this->extendOAuthClient
			->expects($this->once())
			->method('getExtendClientSecret')
			->willReturn($this->encryptedClientSecret);

		$this->assertEquals($this->accessTokenBuilder->getExtendOAuthClientData(), ['clientId' => $this->clientId, 'clientSecret' => $this->encryptedClientSecret, 'accessToken' => null]);
	}

	public function testGetExtendOAuthClientDataWithIntegrationIdProvided()
	{
		$this->scopeConfig
			->expects($this->never())
			->method('getValue');
		$this->extendOAuthClientRepository
			->expects($this->once())
			->method('getByIntegrationId')
			->with((int) $this->integrationId)
			->willReturn($this->extendOAuthClient);
		$this->extendOAuthClient
			->expects($this->once())
			->method('getExtendClientId')
			->willReturn($this->clientId);
		$this->extendOAuthClient
			->expects($this->once())
			->method('getExtendClientSecret')
			->willReturn($this->encryptedClientSecret);

		$this->assertEquals($this->accessTokenBuilder->getExtendOAuthClientData($this->integrationId), ['clientId' => $this->clientId, 'clientSecret' => $this->encryptedClientSecret, 'accessToken' => null]);
	}

	public function testGetExtendOAuthClientDataWithIntegrationIdProvidedAndNoExtendOauth()
	{
		$this->scopeConfig
			->expects($this->never())
			->method('getValue');
		$this->extendOAuthClientRepository
			->expects($this->once())
			->method('getByIntegrationId')
			->with((int) $this->integrationId)
			->willThrowException(new NoSuchEntityException());
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
		$this->storeIntegration
			->expects($this->once())
			->method('getExtendClientId')
			->willReturn($this->clientId);
		$this->storeIntegration
			->expects($this->once())
			->method('getExtendClientSecret')
			->willReturn($this->encryptedClientSecret);

		$this->assertEquals($this->accessTokenBuilder->getExtendOAuthClientData($this->integrationId), ['clientId' => $this->clientId, 'clientSecret' => $this->encryptedClientSecret]);
	}
}
