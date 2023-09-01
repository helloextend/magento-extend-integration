<?php

namespace Extend\Integration\Test\Unit\Model\Config\Frontend;

use Extend\Integration\Test\Utils\PHPUnitUtils;
use Extend\Integration\Service\Extend;
use Extend\Integration\Model\Config\Frontend\EnableCartBalancing;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\TestCase;

class EnableCartBalancingTest extends TestCase
{

  /**
   * @var EnableCartBalancing
   */
  private $model;

  /**
   * @var AbstractElement|\PHPUnit\Framework\MockObject\MockObject
   */
  private $elementMock;

  /**
   * @var Context|\PHPUnit\Framework\MockObject\MockObject
   */
  private $contextMock;

  /**
   * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $scopeConfigMock;

  /**
   * @var Manager|\PHPUnit\Framework\MockObject\MockObject
   */
  private $managerMock;

  /**
   * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $storeManagerMock;

  /**
   * @var SecureHtmlRenderer|\PHPUnit\Framework\MockObject\MockObject
   */
  private $secureRendererMock;


  /**
   * this function does three things:
   * 1. sets up mock objects which are used as arguments for the constructor of the class under test
   * 2. instantiates the class under test using the mock objects
   * 3. sets up mock objects which are used as arguments for the tested functions
   * 
   * this function is called by the framework before each test function.
   * 
   * ensure when writing/updating tests that you mock every dependency!
   * and ensure the requisite methods/functions on each mock object **exist** and are
   * well-mocked!
   * 
   * @return void
   */
  protected function setUp(): void
  {

    // setting up mocks - these are arguments for the EnableCartBalancing constructor
    $this->contextMock = $this->getMockBuilder(Context::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
      ->disableOriginalConstructor()
      ->setMethods(['getValue', 'isSetFlag'])
      ->getMock();

    $this->managerMock = $this->getMockBuilder(Manager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->secureRendererMock = $this->createMock(SecureHtmlRenderer::class);
    $this->secureRendererMock->method('renderEventListenerAsTag')
      ->willReturnCallback(
        function (string $event, string $js, string $selector): string {
          return "<script>document.querySelector('$selector').$event = function () { $js };</script>";
        }
      );
    $this->secureRendererMock->method('renderStyleAsTag')
      ->willReturnCallback(
        function (string $style, string $selector): string {
          return "<style>$selector { $style }</style>";
        }
      );

    // the key to this unit test is to ensure that every constructor argument is mocked!
    // otherwise we'll run into errors where methods are called on null objects.
    $this->model = new EnableCartBalancing(
      $this->contextMock,
      $this->scopeConfigMock,
      $this->managerMock,
      $this->storeManagerMock,
      [],
      $this->secureRendererMock
    );

    // we also need to mock any arguments passed to any tested functions
    $this->elementMock = $this->getMockBuilder(AbstractElement::class)
      ->disableOriginalConstructor()
      ->onlyMethods([
        'getElementHtml',
        'getHtmlId',
        'getName'
      ])
      ->addMethods([
        'setDisabled',
        'setValue',
        'setIsDisableInheritance'
      ])
      ->getMock();
    $this->elementMock
      ->method('getName')
      ->willReturn('test_name');
  }

  public function testGetElementHtmlWithV2Enabled()
  {
    $this->scopeConfigMock->expects($this->once())
      ->method('getValue')
      ->with(Extend::ENABLE_EXTEND)
      ->willReturn(true);

    $this->elementMock->expects($this->never())
      ->method('setDisabled');

    $this->elementMock->expects($this->never())
      ->method('setValue');

    $this->elementMock->expects($this->once())
      ->method('getElementHtml');

    PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);
  }

  public function testGetElementHtmlWithV2Disabled()
  {
    $this->scopeConfigMock->expects($this->once())
      ->method('getValue')
      ->with(Extend::ENABLE_EXTEND)
      ->willReturn(false);

    $this->elementMock->expects($this->once())
      ->method('setDisabled')
      ->with(true);

    $this->elementMock->expects($this->once())
      ->method('setValue')
      ->with(0);

    $this->elementMock->expects($this->once())
      ->method('getElementHtml');

    PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);
  }

  public function testRenderInheritCheckboxWithV2Enabled()
  {
    $this->scopeConfigMock->expects($this->once())
      ->method('getValue')
      ->with(Extend::ENABLE_EXTEND)
      ->willReturn(true);

    $this->elementMock->expects($this->never())
      ->method('setIsDisableInheritance');

    PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
  }

  public function testRenderInheritCheckboxWithV2Disabled()
  {
    $this->scopeConfigMock->expects($this->once())
      ->method('getValue')
      ->with(Extend::ENABLE_EXTEND)
      ->willReturn(false);

    $this->elementMock->expects($this->once())
      ->method('setIsDisableInheritance')
      ->with(true);

    PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
  }
}
