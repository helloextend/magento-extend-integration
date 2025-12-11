<?php

namespace Extend\Integration\Test\Unit\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IntegrationTest extends TestCase
{
    public function testLogErrorToLoggingServiceSetsStoreIdToExtendUuid()
    {
        $extendUuid = '00000000-1111-2222-3333-444444444444';
        $magentoStoreUuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $storeId = 3;

        $curl = $this->getMockBuilder(Curl::class)
            ->onlyMethods(['setHeaders', 'post'])
            ->disableOriginalConstructor()
            ->getMock();
        $serializer = $this->createMock(SerializerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $envBuilder = $this->createMock(ActiveEnvironmentURLBuilder::class);
        $tokenBuilder = $this->createMock(AccessTokenBuilder::class);
        $storeIntegrationRepo = $this->createMock(StoreIntegrationRepositoryInterface::class);

        $envBuilder->method('getIntegrationURL')->willReturn('https://integration.example');
        $tokenBuilder->method('getAccessToken')->willReturn('token');
        
        $curl->expects($this->once())->method('setHeaders');

        $storeIntegration = $this->getMockBuilder('Extend\\Integration\\Api\\Data\\StoreIntegrationInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeIntegration->method('getExtendStoreUuid')->willReturn($extendUuid);
        $storeIntegration->method('getStoreUuid')->willReturn($magentoStoreUuid);
        $storeIntegrationRepo->method('getByStoreIdAndActiveEnvironment')->with($storeId)->willReturn($storeIntegration);

        // Let serializer serialize arrays via json_encode
        $serializer->method('serialize')->willReturnCallback(function ($data) {
            return json_encode($data);
        });

        // Assert the curl post receives a body with store_id equal to Extend UUID and store_uuid set
        $curl->expects($this->once())->method('post')->with(
            $this->stringContains('/module/logging'),
            $this->callback(function ($body) use ($extendUuid, $magentoStoreUuid) {
                $decoded = json_decode($body, true);
                return isset($decoded['extend_store_id']) && $decoded['extend_store_id'] === $extendUuid &&
                       isset($decoded['magento_store_id']) && $decoded['magento_store_id'] === $magentoStoreUuid;
            })
        );

        $integration = new Integration(
            $curl,
            $serializer,
            $logger,
            $storeManager,
            $envBuilder,
            $tokenBuilder,
            $storeIntegrationRepo
        );

        $integration->logErrorToLoggingService('Test message', $storeId, 'error');
    }

    public function testLogErrorToLoggingServiceSetsEmptyStoreIdWhenExtendUuidMissing()
    {
        $storeId = 5;

        $curl = $this->getMockBuilder(Curl::class)
            ->onlyMethods(['setHeaders', 'post'])
            ->disableOriginalConstructor()
            ->getMock();
        $serializer = $this->createMock(SerializerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $envBuilder = $this->createMock(ActiveEnvironmentURLBuilder::class);
        $tokenBuilder = $this->createMock(AccessTokenBuilder::class);
        $storeIntegrationRepo = $this->createMock(StoreIntegrationRepositoryInterface::class);

        $envBuilder->method('getIntegrationURL')->willReturn('https://integration.example');
        $tokenBuilder->method('getAccessToken')->willReturn('token');
        
        $curl->expects($this->once())->method('setHeaders');

        $storeIntegration = $this->getMockBuilder('Extend\\Integration\\Api\\Data\\StoreIntegrationInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeIntegration->method('getExtendStoreUuid')->willReturn('');
        $storeIntegration->method('getStoreUuid')->willReturn('');
        $storeIntegrationRepo->method('getByStoreIdAndActiveEnvironment')->with($storeId)->willReturn($storeIntegration);

        // Let serializer serialize arrays via json_encode
        $serializer->method('serialize')->willReturnCallback(function ($data) {
            return json_encode($data);
        });

        $curl->expects($this->once())->method('post')->with(
            $this->stringContains('/module/logging'),
            $this->callback(function ($body) {
                $decoded = json_decode($body, true);
                return isset($decoded['extend_store_id']) && $decoded['extend_store_id'] === '' &&
                       isset($decoded['magento_store_id']) && $decoded['magento_store_id'] === '';
            })
        );

        $integration = new Integration(
            $curl,
            $serializer,
            $logger,
            $storeManager,
            $envBuilder,
            $tokenBuilder,
            $storeIntegrationRepo
        );

        $integration->logErrorToLoggingService('Test message', $storeId, 'error');
    }

    public function testPayloadIncludesStoreIdAndStoreUuid()
    {
        $extendUuid = 'ffff0000-ffff-0000-ffff-000000000000';
        $magentoStoreUuid = 'eeee1111-eeee-1111-eeee-111111111111';
        $storeId = 7;

        $curl = $this->getMockBuilder(Curl::class)
            ->onlyMethods(['setHeaders', 'post'])
            ->disableOriginalConstructor()
            ->getMock();
        $serializer = $this->createMock(SerializerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $envBuilder = $this->createMock(ActiveEnvironmentURLBuilder::class);
        $tokenBuilder = $this->createMock(AccessTokenBuilder::class);
        $storeIntegrationRepo = $this->createMock(StoreIntegrationRepositoryInterface::class);

        $envBuilder->method('getIntegrationURL')->willReturn('https://integration.example');
        $tokenBuilder->method('getAccessToken')->willReturn('token');
        
        $curl->expects($this->once())->method('setHeaders');

        $storeIntegration = $this->getMockBuilder('Extend\\Integration\\Api\\Data\\StoreIntegrationInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeIntegration->method('getExtendStoreUuid')->willReturn($extendUuid);
        $storeIntegration->method('getStoreUuid')->willReturn($magentoStoreUuid);
        $storeIntegrationRepo->method('getByStoreIdAndActiveEnvironment')->with($storeId)->willReturn($storeIntegration);

        // Let serializer serialize arrays via json_encode
        $serializer->method('serialize')->willReturnCallback(function ($data) {
            return json_encode($data);
        });

        // Assert payload contains both store_id and store_uuid
        $curl->expects($this->once())->method('post')->with(
            $this->stringContains('/module/logging'),
            $this->callback(function ($body) use ($extendUuid, $magentoStoreUuid) {
                $decoded = json_decode($body, true);
                return isset($decoded['extend_store_id']) && $decoded['extend_store_id'] === $extendUuid &&
                       isset($decoded['magento_store_id']) && $decoded['magento_store_id'] === $magentoStoreUuid &&
                       isset($decoded['message']) && isset($decoded['timestamp']) && isset($decoded['log_level']);
            })
        );

        $integration = new Integration(
            $curl,
            $serializer,
            $logger,
            $storeManager,
            $envBuilder,
            $tokenBuilder,
            $storeIntegrationRepo
        );

        $integration->logErrorToLoggingService('Test payload structure', $storeId, 'info');
    }

    public function testLogErrorToLoggingServiceIncludesExceptionDetails()
    {
        $extendUuid = 'aaaa0000-aaaa-0000-aaaa-000000000000';
        $magentoStoreUuid = 'bbbb1111-bbbb-1111-bbbb-111111111111';
        $storeId = 9;
        $testException = new \Exception('Test exception message');

        $curl = $this->getMockBuilder(Curl::class)
            ->onlyMethods(['setHeaders', 'post'])
            ->disableOriginalConstructor()
            ->getMock();
        $serializer = $this->createMock(SerializerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $envBuilder = $this->createMock(ActiveEnvironmentURLBuilder::class);
        $tokenBuilder = $this->createMock(AccessTokenBuilder::class);
        $storeIntegrationRepo = $this->createMock(StoreIntegrationRepositoryInterface::class);

        $envBuilder->method('getIntegrationURL')->willReturn('https://integration.example');
        $tokenBuilder->method('getAccessToken')->willReturn('token');
        
        $curl->expects($this->once())->method('setHeaders');

        $storeIntegration = $this->getMockBuilder('Extend\\Integration\\Api\\Data\\StoreIntegrationInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeIntegration->method('getExtendStoreUuid')->willReturn($extendUuid);
        $storeIntegration->method('getStoreUuid')->willReturn($magentoStoreUuid);
        $storeIntegrationRepo->method('getByStoreIdAndActiveEnvironment')->with($storeId)->willReturn($storeIntegration);

        // Let serializer serialize arrays via json_encode
        $serializer->method('serialize')->willReturnCallback(function ($data) {
            return json_encode($data);
        });

        // Assert payload includes exception_message and stack_trace when exception is passed
        $curl->expects($this->once())->method('post')->with(
            $this->stringContains('/module/logging'),
            $this->callback(function ($body) {
                $decoded = json_decode($body, true);
                return isset($decoded['message']) && $decoded['message'] === 'Context message' &&
                       isset($decoded['exception_message']) && $decoded['exception_message'] === 'Test exception message' &&
                       isset($decoded['stack_trace']) && !empty($decoded['stack_trace']);
            })
        );

        $integration = new Integration(
            $curl,
            $serializer,
            $logger,
            $storeManager,
            $envBuilder,
            $tokenBuilder,
            $storeIntegrationRepo
        );

        $integration->logErrorToLoggingService('Context message', $storeId, 'error', $testException);
    }
}
