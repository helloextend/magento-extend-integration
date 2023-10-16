<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Extend\Integration\Model\ExtendOAuthClientRepository;
use Extend\Integration\Model\ResourceModel\ExtendOAuthClient as ExtendOAuthClientResource;
use Extend\Integration\Model\ResourceModel\ExtendOAuthClient\CollectionFactory as ExtendOAuthClientCollectionFactory;
use Extend\Integration\Model\ResourceModel\ExtendOAuthClient\Collection as ExtendOAuthClientCollection;
use Extend\Integration\Model\ExtendOAuthClientFactory;
use Extend\Integration\Model\ExtendOAuthClient;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Oauth\Consumer;
use Magento\Integration\Model\Integration;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ExtendOAuthClientRepositoryTest extends TestCase
{
    /** @var ExtendOAuthClientRepository */
    private $extendOAuthClientRepository;

    /** @var ExtendOAuthClientResource */
    private $extendOAuthClientResourceMock;

    /** @var ExtendOAuthClientCollectionFactory */
    private $extendOAuthClientCollectionFactoryMock;

    /** @var ExtendOAuthClientFactory */
    private $extendOAuthClientFactoryMock;

    /** @var IntegrationServiceInterface */
    private $integrationServiceMock;

    /** @var OauthServiceInterface */
    private $oauthServiceMock;

    /** @var EncryptorInterface */
    private $encryptorMock;

    /** @var Consumer */
    private $consumerMock;

    /** @var Consumer */
    private $consumerNotMatchingIntegrationIdMock;

    /** @var Consumer */
    private $consumerNotFoundMock;

    /** @var Consumer */
    private $consumerForIntegrationNotFoundMock;

    /** @var int */
    private $consumerIdMock = 1;

    /** @var int */
    private $consmerIdNotMatchingIntegrationIdMock = 2;

    /** @var int */
    private $consumerIdForIntegrationNotFoundMock = 44044;

    /** @var string */
    private $consumerKeyNotFoundMock = '404';

    /** @var string */
    private $consumerKeyForIntegrationNotFoundMock = '44044';

    /** @var string */
    private $consumerKeyMock = '1';

    /** @var string */
    private $consumerKeyNotMatchingIntegrationIdMock = '2';

    /** @var Integration */
    private $integrationMock;

    /** @var Integration */
    private $integrationNotMatchingConsumerIdMock;

    /** @var Integration */
    private $integrationNotFoundMock;

    /** @var int */
    private $integrationIdMock = 1;

    /** @var int */
    private $integrationIdNotMatchingConsumerIdMock = 999;

    /** @var string */
    private $clientId = 'client_id';

    /** @var string */
    private $clientSecret = 'client_secret';

    /** @var string */
    private $encryptedClientSecret = 'encrypted_client_secret';

    protected function setUp(): void
    {
        // Mock dependencies
        $this->extendOAuthClientResourceMock = $this->createStub(ExtendOAuthClientResource::class);
        $this->extendOAuthClientCollectionFactoryMock = $this->createStub(ExtendOAuthClientCollectionFactory::class);
        $this->extendOAuthClientFactoryMock = $this->createStub(ExtendOAuthClientFactory::class);
        $this->integrationServiceMock = $this->createStub(IntegrationServiceInterface::class);
        $this->oauthServiceMock = $this->createStub(OauthServiceInterface::class);
        $this->encryptorMock = $this->createStub(EncryptorInterface::class);

        // Create stubs for the consumers and integrations
        $this->consumerMock = $this->createStub(Consumer::class);
        $this->consumerNotMatchingIntegrationIdMock = $this->createStub(Consumer::class);
        $this->consumerNotFoundMock = $this->createStub(Consumer::class);
        $this->consumerForIntegrationNotFoundMock = $this->createStub(Consumer::class);
        $this->integrationMock = $this->createStub(Integration::class);
        $this->integrationNotMatchingConsumerIdMock = $this->createStub(Integration::class);
        $this->integrationNotFoundMock = $this->createStub(Integration::class);

        // Map the mocked consumers to their ids/keys
        $this->consumerMock->method('getId')->willReturn($this->consumerIdMock);
        $this->consumerNotMatchingIntegrationIdMock->method('getId')->willReturn($this->consmerIdNotMatchingIntegrationIdMock);
        $this->consumerNotFoundMock->method('getId')->willReturn(null);
        $this->consumerForIntegrationNotFoundMock->method('getId')->willReturn($this->consumerIdForIntegrationNotFoundMock);

        $this->oauthServiceMock->method('loadConsumerByKey')
            ->will($this->returnValueMap([
                [$this->consumerKeyMock, $this->consumerMock],
                [$this->consumerKeyNotMatchingIntegrationIdMock, $this->consumerNotMatchingIntegrationIdMock],
                [$this->consumerKeyNotFoundMock, $this->consumerNotFoundMock],
                [$this->consumerKeyForIntegrationNotFoundMock, $this->consumerForIntegrationNotFoundMock]
            ]));

        // Map the mocked integrations to their ids/keys
        $this->integrationMock->method('getId')->willReturn($this->integrationIdMock);
        $this->integrationNotMatchingConsumerIdMock->method('getId')->willReturn($this->integrationIdNotMatchingConsumerIdMock);
        $this->integrationNotFoundMock->method('getId')->willReturn(null);

        $this->integrationServiceMock->method('findByConsumerId')
            ->will($this->returnValueMap([
                [$this->consumerIdMock, $this->integrationMock],
                [$this->consmerIdNotMatchingIntegrationIdMock, $this->integrationNotMatchingConsumerIdMock],
                [$this->consumerIdForIntegrationNotFoundMock, $this->integrationNotFoundMock]
            ]));

        // Mock the encryptor to return the encrypted client secret
        $this->encryptorMock->method('encrypt')->willReturn($this->encryptedClientSecret);

        // Create the instance under test
        $this->extendOAuthClientRepository = new ExtendOAuthClientRepository(
            $this->extendOAuthClientResourceMock,
            $this->extendOAuthClientCollectionFactoryMock,
            $this->extendOAuthClientFactoryMock,
            $this->integrationServiceMock,
            $this->oauthServiceMock,
            $this->encryptorMock
        );
    }

    public function testGetByIntegrationIdWithExistingEntity()
    {
        // Mock entity data
        $integrationId = 1;

        // Create the mock client entity
        $extendOAuthClient = $this->createStub(ExtendOAuthClient::class);

        // Mock the client factory to return the mock client entity
        $extendOAuthClient->method('getId')->willReturn($integrationId);
        $this->extendOAuthClientFactoryMock->method('create')->willReturn($extendOAuthClient);
        $this->extendOAuthClientResourceMock->method('load')->willReturn($extendOAuthClient);

        // Execute the test
        $result = $this->extendOAuthClientRepository->getByIntegrationId($integrationId);

        // Expect that the returned entity is the existing entity
        $this->assertEquals($extendOAuthClient, $result);
    }

    public function testGetByIntegrationIdWithNoExistingEntity()
    {
        // Mock entity data
        $integrationId = 1;

        // Create the mock client entity
        $extendOAuthClient = $this->createStub(ExtendOAuthClient::class);

        // Mock the client factory to return null, indicating the entity does not exist
        $extendOAuthClient->method('getId')->willReturn(null);
        $this->extendOAuthClientFactoryMock->method('create')->willReturn($extendOAuthClient);
        $this->extendOAuthClientResourceMock->method('load')->willReturn($extendOAuthClient);

        // Expect an exception to be thrown
        $this->expectException(NoSuchEntityException::class);

        // Execute the test
        $this->extendOAuthClientRepository->getByIntegrationId($integrationId);
    }

    public function testGetListWithExistingEntities()
    {
        // Mock the collection
        $collection = $this->createStub(ExtendOAuthClientCollection::class);

        // Create client entities
        $extendOAuthClient1 = $this->createStub(ExtendOAuthClient::class);
        $extendOAuthClient2 = $this->createStub(ExtendOAuthClient::class);

        // Mock the collection to return the client entities
        $collection->method('getItems')->willReturn([$extendOAuthClient1, $extendOAuthClient2]);
        $this->extendOAuthClientCollectionFactoryMock->method('create')->willReturn($collection);

        // Execute the test
        $result = $this->extendOAuthClientRepository->getList();

        // Expect the result contains the expected client entities
        $this->assertEquals([$extendOAuthClient1, $extendOAuthClient2], $result);
    }

    public function testGetListWithNoExistingEntities()
    {
        // Mock the collection
        $collection = $this->createStub(ExtendOAuthClientCollection::class);

        // Mock the collection to return an empty array, indicating no existing entities
        $collection->method('getItems')->willReturn([]);
        $this->extendOAuthClientCollectionFactoryMock->method('create')->willReturn($collection);

        // Execute the test
        $result = $this->extendOAuthClientRepository->getList();

        // Expect the result contains an empty array
        $this->assertEquals([], $result);
    }

    public function testSaveClientWithNoExistingIntegration()
    {
        // Mock the client instance
        $extendOAuthClient = $this->createStub(ExtendOAuthClient::class);
        $this->extendOAuthClientFactoryMock->method('create')->willReturn($extendOAuthClient);

        // Expect load() to be called with the integrationId
        $this->extendOAuthClientResourceMock->method('load')
            ->with(
                $extendOAuthClient,
                $this->integrationIdMock,
                ExtendOAuthClient::INTEGRATION_ID
            )
            ->willReturn($extendOAuthClient);

        // Return no existing entity
        $extendOAuthClient->method('getId')->willReturn(null);

        // Expect the integrationId to be set
        $extendOAuthClient->expects($this->once())->method('setIntegrationId')->with($this->integrationIdMock);

        // Expect the clientId to be set
        $extendOAuthClient->expects($this->once())->method('setExtendClientId')->with($this->clientId);

        // Expect the clientSecret to be set with the encrypted secret
        $extendOAuthClient->expects($this->once())->method('setExtendClientSecret')->with($this->encryptedClientSecret);

        // Expect save() to be called with the new client to be created
        $this->extendOAuthClientResourceMock->expects($this->once())->method('save')->with($extendOAuthClient);

        // Expect the client secret to be encrypted
        $this->encryptorMock->expects($this->once())
            ->method('encrypt')
            ->with($this->clientSecret);

        // Execute the test
        $this->extendOAuthClientRepository->saveClientToIntegration(
            $this->consumerKeyMock,
            $this->clientId,
            $this->clientSecret
        );
    }

    public function testSaveClientWithExistingIntegration()
    {
        // Mock the client instance
        $extendOAuthClient = $this->createStub(ExtendOAuthClient::class);
        $this->extendOAuthClientFactoryMock->method('create')->willReturn($extendOAuthClient);

        // Expect load() to be called with the integrationId
        $this->extendOAuthClientResourceMock->method('load')
            ->with(
                $extendOAuthClient,
                $this->integrationIdMock,
                ExtendOAuthClient::INTEGRATION_ID
            )
            ->willReturn($extendOAuthClient);

        // Return the integrationId, indicating an existing entity
        $extendOAuthClient->method('getId')->willReturn($this->integrationIdMock);

        // Expect that the integrationId is not set (it already exists)
        $extendOAuthClient->expects($this->never())->method('setIntegrationId');

        // Expect the clientId to be set
        $extendOAuthClient->expects($this->once())->method('setExtendClientId')->with($this->clientId);

        // Expect the clientSecret to be set with the encrypted secret
        $extendOAuthClient->expects($this->once())->method('setExtendClientSecret')->with($this->encryptedClientSecret);

        // Expect save() to be called with the existing client to be updated
        $this->extendOAuthClientResourceMock->expects($this->once())->method('save')->with($extendOAuthClient);

        // Expect the client secret to be encrypted
        $this->encryptorMock->expects($this->once())
            ->method('encrypt')
            ->with($this->clientSecret);

        // Execute the test
        $this->extendOAuthClientRepository->saveClientToIntegration(
            $this->consumerKeyMock,
            $this->clientId,
            $this->clientSecret
        );
    }

    public function testSaveClientWhenConsumerKeyAndInterationIdAreNotTheSame()
    {
        // Mock the client instance
        $extendOAuthClient = $this->createStub(ExtendOAuthClient::class);
        $this->extendOAuthClientFactoryMock->method('create')->willReturn($extendOAuthClient);

        // Expect load() to be called with the integrationId
        $this->extendOAuthClientResourceMock->method('load')
            ->with(
                $extendOAuthClient,
                $this->integrationIdNotMatchingConsumerIdMock,
                ExtendOAuthClient::INTEGRATION_ID
            )
            ->willReturn($extendOAuthClient);

        // Return the integrationId, indicating an existing entity
        $extendOAuthClient->method('getId')->willReturn($this->integrationIdNotMatchingConsumerIdMock);

        // Expect that the integrationId is not set (it already exists)
        $extendOAuthClient->expects($this->never())->method('setIntegrationId');

        // Expect the clientId to be set
        $extendOAuthClient->expects($this->once())->method('setExtendClientId')->with($this->clientId);

        // Expect the clientSecret to be set with the encrypted secret
        $extendOAuthClient->expects($this->once())->method('setExtendClientSecret')->with($this->encryptedClientSecret);

        // Expect save() to be called with the existing client to be updated
        $this->extendOAuthClientResourceMock->expects($this->once())->method('save')->with($extendOAuthClient);

        // Expect the client secret to be encrypted
        $this->encryptorMock->expects($this->once())
            ->method('encrypt')
            ->with($this->clientSecret);

        // Execute the test
        $this->extendOAuthClientRepository->saveClientToIntegration(
            $this->consumerKeyNotMatchingIntegrationIdMock,
            $this->clientId,
            $this->clientSecret
        );
    }

    public function testExceptionOnSaveClientWhenConsumerDoesNotExist()
    {
        // Expect an exception to be thrown
        $this->expectException(NoSuchEntityException::class);

        // Execute the test
        $this->extendOAuthClientRepository->saveClientToIntegration(
            $this->consumerKeyNotFoundMock,
            $this->clientId,
            $this->clientSecret
        );

        // Expect the client not to have been saved since it ended early
        $this->extendOAuthClientResourceMock->expects($this->never())->method('save');
    }

    public function testExceptionOnSaveClientWhenIntegrationDoesNotExist()
    {
        // Expect an exception to be thrown
        $this->expectException(NoSuchEntityException::class);

        // Execute the test
        $this->extendOAuthClientRepository->saveClientToIntegration(
            $this->consumerKeyForIntegrationNotFoundMock,
            $this->clientId,
            $this->clientSecret
        );

        // Expect the client not to have been saved since it ended early
        $this->extendOAuthClientResourceMock->expects($this->never())->method('save');
    }
}
