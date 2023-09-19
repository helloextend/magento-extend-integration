<?php

namespace Extend\Integration\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Extend\Integration\Model\StoreIntegrationRepository;
use Magento\Store\Model\StoreManager;
use Magento\Framework\DataObject\IdentityService;
use Extend\Integration\Model\StoreIntegrationFactory;
use Extend\Integration\Model\ResourceModel\StoreIntegration as StoreIntegrationResource;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Extend\Integration\Model\ResourceModel\StoreIntegration\CollectionFactory;
use Extend\Integration\Model\ResourceModel\StoreIntegration\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Magento\Integration\Model\Oauth\Consumer;
use Magento\Integration\Model\Integration;

class StoreIntegrationRepositoryTest extends TestCase
{

  /**
   * @var string
   */
    private $consumerKeyMock1;

  /**
   * @var string
   */
    private $consumerKeyMock2;

  /**
   * @var string
   */
    private $consumerKeyNoIntegrationMock;

  /**
   * @var string
   */
    private $consumerKeyInvalidMock;

  /**
   * @var string
   */
    private $consumerKeyNoStoresMock;

  /**
   * @var string
   */
    private $encryptedClientSecret;

  /**
   * @var int
   */
    private $consumerIdMock1;

  /**
   * @var int
   */
    private $consumerIdMock2;

    /**
     * @var int
     */
    private $consumerIdMock3;

    /**
     * @var int
     */
    private $consumerIdMock4;

  /**
   * @var int
   */
    private $integrationIdMock1;

  /**
   * @var int
   */
    private $integrationIdMock2;

    /**
     * @var int
     */
    private $integrationIdMock4;

    /**
     * @var int
     */
    private $someDifferentIntegrationIdMock;

    /**
     * @var StoreIntegrationRepository
     */
    private $testSubject;

    /**
     * @var StoreManager | MockObject
     */
    private $storeManagerMock;

    /**
     * @var IdentityService | MockObject
     */
    private $identityServiceMock;

    /**
     * @var StoreIntegrationFactory | MockObject
     */
    private $storeIntegrationFactoryMock;

    /**
     * @var StoreIntegrationResource | MockObject
     */
    private $storeIntegrationResourceMock;

    /**
     * @var OauthServiceInterface | MockObject
     *
     */
    private $oauthServiceInterfaceMock;

    /**
     * @var IntegrationServiceInterface | MockObject
     */
    private $integrationServiceInterfaceMock;

    /**
     * @var CollectionFactory | MockObject
     */
    private $storeIntegrationCollectionFactoryMock;

    /**
     * @var ScopeConfigInterface | MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var EncryptorInterface | MockObject
     */
    private $encryptorInterfaceMock;

    /**
     * @var ActiveEnvironmentURLBuilder | MockObject
     */
    private $activeEnvironmentURLBuilderMock;

    /**
     * @var Consumer | MockObject
     */
    private $consumerMock1;

    /**
     * @var Consumer | MockObject
     */
    private $consumerMock2;

    /**
     * @var Consumer | MockObject
     */
    private $consumerMock3;

    /**
     * @var Consumer | MockObject
     */
    private $consumerMock4;

    /**
     * @var Integration | MockObject
     */
    private $integrationMock1;

    /**
     * @var Integration | MockObject
     */
    private $integrationMock2;

    /**
     * @var Integration | MockObject
     */
    private $integrationMock4;

    /**
     * @var Collection | MockObject
     */
    private $storeIntegrationCollectionMock;

    protected function setUp(): void
    {
        // set primitive test values

        // consumer 1 and integration 1 have the same ID
        $this->consumerIdMock1 = 123;
        $this->integrationIdMock1 = $this->consumerIdMock1;

        // consumer 2 and integration 2 have differing IDs
        $this->consumerIdMock2 = 456;
        $this->someDifferentIntegrationIdMock = 1234567890;
        $this->integrationIdMock2 = $this->someDifferentIntegrationIdMock;

        // consumer 3 has no associated integration
        $this->consumerIdMock3 = 789;

        //consumer 4 has no associated stores
        $this->consumerIdMock4 = 987;
        $this->integrationIdMock4 = $this->consumerIdMock4;

        $this->encryptedClientSecret = 'some_encrypted_client_secret';

        // create mock constructor args for the tested class
        $this->storeManagerMock = $this->createStub(StoreManager::class);
        $this->identityServiceMock = $this->createStub(IdentityService::class);
        $this->storeIntegrationFactoryMock = $this->createStub(StoreIntegrationFactory::class);
        $this->storeIntegrationResourceMock = $this->createStub(StoreIntegrationResource::class);
        $this->oauthServiceInterfaceMock = $this->createStub(OauthServiceInterface::class);
        $this->integrationServiceInterfaceMock = $this->createStub(IntegrationServiceInterface::class);
        $this->storeIntegrationCollectionFactoryMock = $this->createStub(CollectionFactory::class);
        $this->scopeConfigInterfaceMock = $this->createStub(ScopeConfigInterface::class);
        $this->encryptorInterfaceMock = $this->createStub(EncryptorInterface::class);
        $this->activeEnvironmentURLBuilderMock = $this->createStub(ActiveEnvironmentURLBuilder::class);

        // create the class to test
        $this->testSubject = new StoreIntegrationRepository(
            $this->storeManagerMock,
            $this->identityServiceMock,
            $this->storeIntegrationFactoryMock,
            $this->storeIntegrationResourceMock,
            $this->oauthServiceInterfaceMock,
            $this->integrationServiceInterfaceMock,
            $this->storeIntegrationCollectionFactoryMock,
            $this->scopeConfigInterfaceMock,
            $this->encryptorInterfaceMock,
            $this->activeEnvironmentURLBuilderMock
        );

      // create arguments for tested method(s)
        $this->consumerKeyMock1 = 'some_mock_consumer_key_1';
        $this->consumerKeyMock2 = 'some_mock_consumer_key_2';
        $this->consumerKeyNoIntegrationMock = 'some_mock_consumer_key_3';
        $this->consumerKeyNoStoresMock = 'some_mock_consumer_key_4';
        $this->consumerKeyInvalidMock = 'invalid_consumer_key';
        $this->encryptedClientSecret = 'some_encrypted_client_secret';

      // additional setup needed to cover the permutations in the test cases below
        $this->consumerMock1 = $this->createConfiguredMock(Consumer::class, [
          'getId' => $this->consumerIdMock1
        ]);
        $this->consumerMock2 = $this->createConfiguredMock(Consumer::class, [
          'getId' => $this->consumerIdMock2
        ]);
        $this->consumerMock3 = $this->createConfiguredMock(Consumer::class, [
          'getId' => $this->consumerIdMock3
        ]);
        $this->consumerMock4 = $this->createConfiguredMock(Consumer::class, [
          'getId' => $this->consumerIdMock4
        ]);
        $this->integrationMock1 = $this->getMockBuilder(Integration::class)->disableOriginalConstructor()->onlyMethods(['getId'])->addMethods(['setClientId', 'setClientSecret'])->getMock();
        $this->integrationMock1
            ->method('getId')
            ->willReturn($this->integrationIdMock1);
        $this->integrationMock1
            ->method('setClientId')
            ->willReturn(null);
        $this->integrationMock1
            ->method('setClientSecret')
            ->willReturn(null);
        $this->integrationMock2 = $this->createConfiguredMock(Integration::class, [
          'getId' => $this->integrationIdMock2
        ]);
        $this->integrationMock4 = $this->createConfiguredMock(Integration::class, [
          'getId' => $this->integrationIdMock4
        ]);
        $this->encryptorInterfaceMock
          ->method('encrypt')
          ->willReturn($this->encryptedClientSecret);
        $this->oauthServiceInterfaceMock
            ->method('loadConsumerByKey')
            ->will($this->returnValueMap([
              [ $this->consumerKeyMock1, $this->consumerMock1 ],
              [ $this->consumerKeyMock2, $this->consumerMock2 ],
              [ $this->consumerKeyNoIntegrationMock, $this->consumerMock3],
              [ $this->consumerKeyNoStoresMock, $this->consumerMock4],
              [ $this->consumerKeyInvalidMock, null ]
            ]));
        $this->integrationServiceInterfaceMock
            ->method('findByConsumerId')
            ->will($this->returnValueMap([
              [ $this->consumerIdMock1, $this->integrationMock1],
              [ $this->consumerIdMock2, $this->integrationMock2],
              [ $this->consumerIdMock4, $this->integrationMock4]
            ]));
        $this->storeIntegrationCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
              'addFieldToFilter',
              'addFieldToSelect',
              'load',
              'getColumnValues',
              'getIterator'
            ])
            ->getMock();
        $this->storeIntegrationCollectionMock
            ->method('addFieldToSelect')
            ->willReturn($this->storeIntegrationCollectionMock);
        $this->storeIntegrationCollectionMock
            ->method('load')
            ->willReturn($this->storeIntegrationCollectionMock);
        $this->storeIntegrationCollectionFactoryMock
            ->method('create')
            ->willReturn($this->storeIntegrationCollectionMock);
    }

    public function testGetListByConsumerKeyWhereConsumerIdAndIntegrationIdAreIdentical()
    {
        $this->oauthServiceInterfaceMock
        ->expects($this->once())
        ->method('loadConsumerByKey')
        ->with($this->consumerKeyMock1)
        ->willReturn($this->consumerMock1);

        $this->integrationServiceInterfaceMock
        ->expects($this->once())
        ->method('findByConsumerId')
        ->with($this->consumerIdMock1)
        ->willReturn($this->integrationMock1);

        // ensure `addFieldToFilter` is called with the right value
        $this->storeIntegrationCollectionMock
        ->method('addFieldToFilter')
        ->with('integration_id', $this->integrationIdMock1) // same value as consumerIdMock1
        ->willReturn($this->storeIntegrationCollectionMock);

        $this->storeIntegrationCollectionMock
            ->method('getColumnValues')
            ->with('store_id')
            ->willReturn([1]);

        $this->testSubject->getListByConsumerKey($this->consumerKeyMock1);
    }

    public function testGetListByConsumerKeyWhereConsumerIdAndIntegrationIdAreDifferent()
    {
        $this->oauthServiceInterfaceMock
            ->expects($this->once())
            ->method('loadConsumerByKey')
            ->with($this->consumerKeyMock2)
            ->willReturn($this->consumerMock2);

        $this->integrationServiceInterfaceMock
            ->expects($this->once())
            ->method('findByConsumerId')
            ->with($this->consumerIdMock2)
            ->willReturn($this->integrationMock2);

        // this is the most important assertion. we are testing that the
        // ensure `addFieldToFilter` is called with the right value
        $this->storeIntegrationCollectionMock
            ->method('addFieldToFilter')
            ->with('integration_id', $this->integrationIdMock2) // this value is DIFFERENT from consumerIdMock2
            ->willReturn($this->storeIntegrationCollectionMock);

        $this->storeIntegrationCollectionMock
            ->method('getColumnValues')
            ->with('store_id')
            ->willReturn([2]);

        $this->testSubject->getListByConsumerKey($this->consumerKeyMock2);
    }

    public function testGetListByConsumerKeyWhereConsumerIdIsNotAssociatedWithKey()
    {
        $this->oauthServiceInterfaceMock
            ->expects($this->once())
            ->method('loadConsumerByKey')
            ->with($this->consumerKeyInvalidMock)
            ->willReturn(null);

        $this->integrationServiceInterfaceMock
            ->expects($this->never())
            ->method('findByConsumerId');

        $this->storeIntegrationCollectionMock
            ->expects($this->never())
            ->method('addFieldToFilter');

        $this->storeIntegrationCollectionMock
            ->expects($this->never())
            ->method('getColumnValues');

        $this->testSubject->getListByConsumerKey($this->consumerKeyInvalidMock);
    }

    public function testGetListByConsumerKeyWhereIntegrationIdIsNotAssociatedWithConsumerId()
    {
        $this->oauthServiceInterfaceMock
            ->expects($this->once())
            ->method('loadConsumerByKey')
            ->with($this->consumerKeyNoIntegrationMock)
            ->willReturn($this->consumerMock3);

        $this->integrationServiceInterfaceMock
            ->expects($this->once())
            ->method('findByConsumerId')
            ->with($this->consumerIdMock3)
            ->willReturn(null);

        $this->storeIntegrationCollectionMock
            ->expects($this->never())
            ->method('addFieldToFilter');

        $this->storeIntegrationCollectionMock
            ->expects($this->never())
            ->method('getColumnValues');

        $this->testSubject->getListByConsumerKey($this->consumerKeyNoIntegrationMock);
    }

    public function testGetListByConsumerKeyWhereIntegrationIdIsAssociatedWithConsumerIdButNoStoresAreAssociatedWithIntegrationId()
    {
        $this->oauthServiceInterfaceMock
            ->expects($this->once())
            ->method('loadConsumerByKey')
            ->with($this->consumerKeyNoStoresMock)
            ->willReturn($this->consumerMock4);

        $this->integrationServiceInterfaceMock
            ->expects($this->once())
            ->method('findByConsumerId')
            ->with($this->consumerIdMock4)
            ->willReturn($this->integrationMock4);

        $this->storeIntegrationCollectionMock
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->with('integration_id', $this->integrationIdMock4)
            ->willReturn($this->storeIntegrationCollectionMock);

        $this->storeIntegrationCollectionMock
            ->expects($this->once())
            ->method('getColumnValues')
            ->with('store_id')
            ->willReturn([]);

        $this->testSubject->getListByConsumerKey($this->consumerKeyNoStoresMock);
    }

    public function testAttachClientIdAndSecretToIntegration()
    {
        $clientId = 'some_client_id';
        $clientSecret = 'some_client_secret';

        $this->oauthServiceInterfaceMock
            ->expects($this->once())
            ->method('loadConsumerByKey')
            ->with($this->consumerKeyMock1)
            ->willReturn($this->consumerMock1);

        $this->integrationServiceInterfaceMock
            ->expects($this->once())
            ->method('findByConsumerId')
            ->with($this->consumerIdMock1)
            ->willReturn($this->integrationMock1);

        $this->storeIntegrationCollectionMock
            ->method('addFieldToFilter')
            ->with('integration_id', $this->integrationIdMock1)
            ->willReturn($this->storeIntegrationCollectionMock);

        $this->storeIntegrationCollectionMock
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->integrationMock1]));

        $this->integrationMock1
            ->expects($this->once())
            ->method('setClientId')
            ->with($clientId);

        $this->encryptorInterfaceMock
            ->expects($this->once())
            ->method('encrypt')
            ->with($clientSecret)
            ->willReturn($this->encryptedClientSecret);

        $this->integrationMock1
            ->expects($this->once())
            ->method('setClientSecret')
            ->with($this->encryptedClientSecret);

        $this->testSubject->attachClientIdAndSecretToIntegration($this->consumerKeyMock1, $clientId, $clientSecret);
    }
}
