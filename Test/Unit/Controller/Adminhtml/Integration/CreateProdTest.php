<?php

namespace Extend\Integration\Test\Unit\Adminhtml\Integration;

use PHPUnit\Framework\TestCase;
use Extend\Integration\Controller\Adminhtml\Integration\Create;
use Extend\Integration\Controller\Adminhtml\Integration\CreateProd;

class CreateProdTest extends TestCase
{
  /**
   * @var Create
   */
    private $testSubject;

  /**
   * @var \Magento\Backend\App\Action\Context&\PHPUnit\Framework\MockObject\Stub
   */
    private $context;

  /**
   * @var \Magento\Integration\Model\IntegrationService&\PHPUnit\Framework\MockObject\MockObject
   */
    private $integrationService;

  /**
   * @var \Magento\Integration\Model\ConfigBasedIntegrationManager&\PHPUnit\Framework\MockObject\Stub
   */
    private $configBasedIntegrationManager;

  /**
   * @var \Magento\Integration\Model\AuthorizationService&\PHPUnit\Framework\MockObject\Stub
   */
    private $authorizationService;

  /**
   * @var \Magento\Framework\Message\ManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
    private $messageManager;

  /**
   * @var \Magento\Integration\Model\Integration&\PHPUnit\Framework\MockObject\Stub
   */
    private $integrationModel;

  /**
   * @var \Magento\Framework\App\Response\Http&\PHPUnit\Framework\MockObject\MockObject
   */
    private $response;

  /**
   * @var \Magento\Store\App\Response\Redirect&\PHPUnit\Framework\MockObject\Stub
   */
    private $redirect;

  /**
   * @var \Magento\Backend\Helper\Data&\PHPUnit\Framework\MockObject\Stub
   */
    private $helper;

    protected function setUp(): void
    {
      // Create Stubs
        $this->context = $this->createStub(\Magento\Backend\App\Action\Context::class);
        $this->integrationService = $this->createMock(\Magento\Integration\Model\IntegrationService::class);
        $this->configBasedIntegrationManager = $this->createStub(\Magento\Integration\Model\ConfigBasedIntegrationManager::class);
        $this->authorizationService = $this->createStub(\Magento\Integration\Model\AuthorizationService::class);
        $this->messageManager = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->integrationModel = $this->getMockBuilder(\Magento\Integration\Model\Integration::class)
        ->addMethods(
            ['getIntegrationId']
        )
        ->disableOriginalConstructor()
        ->getMock();
        $this->response = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->redirect = $this->createStub(\Magento\Store\App\Response\Redirect::class);
        $this->helper = $this->createStub(\Magento\Backend\Helper\Data::class);
    }

    protected function setupTest($integrationId = null)
    {
        $this->context->method('getRedirect')->willReturn($this->redirect);
        $this->context->method('getResponse')->willReturn($this->response);
        $this->context->method('getHelper')->willReturn($this->helper);
        $this->integrationService->method('findByName')->willReturn($this->integrationModel);
        $this->integrationModel->method('getIntegrationId')->willReturn($integrationId);

      // Create the test subject
        $this->testSubject = new CreateProd(
            $this->context,
            $this->integrationService,
            $this->configBasedIntegrationManager,
            $this->authorizationService,
            $this->messageManager
        );
    }

    public function testExecuteSuccessful()
    {
        $this->setupTest();
        $this->integrationService->expects($this->once())->method('update');
        $this->messageManager->expects($this->once())->method('addSuccessMessage');
        $this->messageManager->expects($this->never())->method('addErrorMessage');
        $this->testSubject->execute();
    }

    public function testExecuteIntegrationAlreadyExists()
    {
        $this->setupTest(123);
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($this->stringContains('already exists'));
        $this->testSubject->execute();
    }

    public function testExecuteErrorThrown()
    {
        $this->setupTest();
        $this->configBasedIntegrationManager->method('processIntegrationConfig')->willThrowException(new \Exception());
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($this->stringContains('Error creating'));
        $this->testSubject->execute();
    }
}
