<?php

namespace Extend\Integration\Test\Unit\Model\Config\Frontend;

use Extend\Integration\Test\Utils\PHPUnitUtils;
use Extend\Integration\Service\Extend;
use Extend\Integration\Model\Config\Frontend\EnableProductProtection;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\TestCase;

class EnableProductProtectionTest extends TestCase
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
   * @var \Magento\Store\Api\Data\StoreInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $mockStore;

  /**
   * @var SecureHtmlRenderer|\PHPUnit\Framework\MockObject\MockObject
   */
  private $secureRendererMock;

  /**
   * @var int
   */
  private $mockStoreId;


  /**
   * setup function
   *
   * @return void
   */
  protected function setUp(): void
  {

    // setting up mocks - these are arguments for the EnableProductProtection constructor
    $this->contextMock = $this->getMockBuilder(Context::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
      ->disableOriginalConstructor()
      ->setMethods(['getValue', 'isSetFlag'])
      ->getMock();

    $this->managerMock = $this->getMockBuilder(Manager::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEnabled'])
      ->getMock();

    $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getStores'])
      ->getMockForAbstractClass();

    $this->mockStoreId = 123;
    $this->mockStore = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
    $this->mockStore
      ->method('getId')
      ->willReturn($this->mockStoreId);

    $this->storeManagerMock->method('getStores')
      ->willReturn([$this->mockStore]);

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
    $this->model = new EnableProductProtection(
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
        'setIsDisableInheritance',
        'setComment'
      ])
      ->getMock();
    $this->elementMock
      ->method('getName')
      ->willReturn('test_name');
  }

  public function testGetElementHtmlWithV1EnabledAndExtendWarrantyEnabled()
  {
    $this->setTestConditions(
      [
        'isV1Enabled' => true,
        'isExtendWarrantyEnabled' => true,
        'isV2Enabled' => false
      ]
    );
    $this->elementMock->expects($this->once())
      ->method('setDisabled')
      ->with(true);
    $this->elementMock->expects($this->once())
      ->method('setValue')
      ->with(0);
    $this->elementMock->expects($this->once())
      ->method('setComment')
      ->with(
        __(
          'Magento Product Protection V2 can only be enabled if Magento Product Protection V1 is completely disabled on all stores.'
        )
      );
    PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);
  }

  public function testGetElementHtmlWithV1EnabledAndExtendWarrantyNotEnabledAndV2NotEnabled()
  {
    $this->setTestConditions(
      [
        'isV1Enabled' => true,
        'isExtendWarrantyEnabled' => false,
        'isV2Enabled' => false
      ]
    );
    $this->elementMock->expects($this->once())
      ->method('setDisabled')
      ->with(true);
    $this->elementMock->expects($this->once())
      ->method('setValue')
      ->with(0);
    PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);
  }

  public function testGetElementHtmlWithV1DisabledAndV2Enabled()
  {
    $this->setTestConditions(
      [
        'isV1Enabled' => false,
        'isExtendWarrantyEnabled' => false,
        'isV2Enabled' => true
      ]
    );
    $this->elementMock->expects($this->never())
      ->method('setDisabled');
    $this->elementMock->expects($this->never())
      ->method('setValue');
    PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);
  }

  public function testRenderInheritCheckboxWithV1EnabledAndExtendWarrantyEnabled()
  {
    $this->setTestConditions(
      [
        'isV1Enabled' => true,
        'isExtendWarrantyEnabled' => true,
        'isV2Enabled' => false
      ]
    );
    $this->elementMock->expects($this->once())
      ->method('setIsDisableInheritance')
      ->with(true);
    PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
  }

  public function testRenderInheritCheckboxWithV1EnabledAndExtendWarrantyNotEnabledAndV2NotEnabled()
  {
    $this->setTestConditions(
      [
        'isV1Enabled' => true,
        'isExtendWarrantyEnabled' => false,
        'isV2Enabled' => false
      ]
    );
    $this->elementMock->expects($this->once())
      ->method('setIsDisableInheritance')
      ->with(true);
    PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
  }

  public function testRenderInheritCheckboxWithV1DisabledAndV2Enabled()
  {
    $this->setTestConditions(
      [
        'isV1Enabled' => false,
        'isExtendWarrantyEnabled' => false,
        'isV2Enabled' => true
      ]
    );
    $this->elementMock->expects($this->never())
      ->method('setIsDisableInheritance');
    PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
  }

  /**
   * helper function to set up the test conditions for the above tests.
   *
   * @param array $conditions - array of booleans, in the order:
   * 1. isV1Enabled
   * 2. isExtendWarrantyEnabled
   * 3. isV2Enabled
   * @return void
   */
  private function setTestConditions(
    array $conditions
  ) {
    [
      'isV1Enabled' => $isV1Enabled,
      'isExtendWarrantyEnabled' => $isExtendWarrantyEnabled,
      'isV2Enabled' => $isV2Enabled
    ] = $conditions;

    // create a map of arguments with return values.
    // first three are expected args, fourth is corresponding return value.
    $scopeConfigMockValueMap = [
      // v1
      ['warranty/enableExtend/enable', 'stores', $this->mockStoreId, $isV1Enabled ? 1 : 0],
      // v2
      [Extend::ENABLE_EXTEND, 'default', null, $isV2Enabled ? 1 : 0],
    ];

    // setup: use the map to set up scopeConfig's getValue mock behavior.
    $this->scopeConfigMock->expects($this->any())
      ->method('getValue')
      ->willReturn($this->returnValueMap($scopeConfigMockValueMap));

    // setup: Extend_Warranty disabled
    $this->managerMock->expects($this->any())
      ->method('isEnabled')
      ->with('Extend_Warranty')
      ->willReturn($isExtendWarrantyEnabled);
  }
}
