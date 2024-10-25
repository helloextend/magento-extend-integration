<?php

namespace Extend\Integration\Test\Unit; /* add \Path\To\Dir */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

use Extend\Integration\Model\HealthCheck;
use Extend\Integration\Api\HealthCheckInterface;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Extend\Integration\Api\Data\HealthCheckResponseInterface;
use Extend\Integration\Model\HealthCheckResponse;
use Magento\Integration\Model\IntegrationService;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Integration\Model\Oauth\Consumer;

class HealthCheckTest extends TestCase
{

  /**
   * @var int
   */
    private $mockResponseCode;

    /**
     * @var string
     */
    private $mockIntegrationURL = 'https://integ-mage-mock.extend.com';

  /**
   * @var int
   */
    private $mockConsumerId;

  /**
   * @var string
   */
    private $mockConsumerKey;
  /**
   * @var HealthCheck
   */
    private $testSubject;

  /**
   * @var IntegrationService|MockObject
   */
    private IntegrationService|MockObject $integrationService;

    /**
     * @var ManagerInterface|MockObject
     */
    protected ManagerInterface|MockObject $messageManager;

    /**
     * @var MetadataBuilder|MockObject
     */
    private MetadataBuilder|MockObject $metadataBuilder;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfig;

    /**
     * @var ActiveEnvironmentURLBuilder|MockObject
     */
    private ActiveEnvironmentURLBuilder|MockObject $activeEnvironmentURLBuilder;

    /**
     * @var OauthServiceInterface|MockObject
     */
    private OauthServiceInterface|MockObject $oauthService;

    /**
     * @var JsonFactory|MockObject
     */
    private JsonFactory|MockObject $resultJsonFactory;

    /**
     * @var Curl|MockObject
     */
    private Curl|MockObject $curl;

    /**
     * @var HealthCheckResponseInterface|MockObject
     */
    private HealthCheckResponseInterface|MockObject $healthCheckResponse;

    protected function setUp(): void
    {
        // create mock constructor args for the tested class
        $this->integrationService = $this->createMock(IntegrationService::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->metadataBuilder = $this->createConfiguredMock(MetadataBuilder::class, [
            'execute' => [
                [],
                []
            ]
        ]);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->activeEnvironmentURLBuilder = $this->createMock(ActiveEnvironmentURLBuilder::class);
        $this->oauthService = $this->createMock(OauthServiceInterface::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->curl = $this->getMockBuilder(Curl::class)
            ->onlyMethods(['getStatus', 'get'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->healthCheckResponse = new HealthCheckResponse();

        // set up some primitive mock values for later use
        $activeIntegration = 1;
        $this->mockConsumerId = 123456789;
        $this->mockConsumerKey = '987654321';

        // mock scope config to return the active integration
        $this->scopeConfig->method('getValue')->willReturn($activeIntegration);

        // then set up the integration model, which will be returned by the integration service
        // and which will provide the consumer id
        $integrationModel = $this
            ->getMockBuilder(\Magento\Integration\Model\Integration::class)
            ->addMethods(['getConsumerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $integrationModel->method('getConsumerId')->willReturn($this->mockConsumerId);
        $this->integrationService->method('get')->with($activeIntegration)->willReturn($integrationModel);

        // mock the consumer model, which will be returned by the oauth service and
        // which will provide the consumer key
        $mockConsumer = $this->getMockBuilder(Consumer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getKey'])
            ->getMock();
        $mockConsumer
            ->expects($this->once())
            ->method('getKey')
            ->willReturn($this->mockConsumerKey);
        $this->oauthService->method('loadConsumer')->willReturn($mockConsumer);

        // mock the active environment URL builder to return the base integration URL
        $this->activeEnvironmentURLBuilder->method('getIntegrationURL')->willReturn($this->mockIntegrationURL);
        $this->mockResponseCode = 200;

        // create the class to test
        $this->testSubject = new HealthCheck(
            $this->integrationService,
            $this->messageManager,
            $this->metadataBuilder,
            $this->scopeConfig,
            $this->activeEnvironmentURLBuilder,
            $this->oauthService,
            $this->resultJsonFactory,
            $this->curl,
            $this->healthCheckResponse
        );
    }

    public function testCheckWithSuccessfulResponseFromExtend()
    {
        // expect curl to be called with the correct URL
        $this->curl
            ->expects($this->once())
            ->method('get')
            ->with(
                'https://integ-mage-mock.extend.com' . Integration::EXTEND_INTEGRATION_ENDPOINTS['integration_health_check'] . '/' . $this->mockConsumerKey
            )
            ->willReturn('something OK');
        $this->curl->method('getStatus')->willReturn($this->mockResponseCode);
        // run the test
        $response = $this->testSubject->check();
        // expect the mocked code to be present on the response object
        $this->assertEquals($this->mockResponseCode, $response->getCode());
    }

    public function testCheckWithException()
    {
        // mock the curl request to go horribly wrong
        $this->curl
            ->method('get')
            ->willThrowException(new \Exception('something went horribly wrong'));
        $this->curl->method('getStatus')->willReturn($this->mockResponseCode);

        // run the test
        $response = $this->testSubject->check();

        // expect the response to have a 500 code and the exception message
        $this->assertEquals(500, $response->getCode());
        $this->assertEquals('something went horribly wrong', $response->getMessage());
    }
}
