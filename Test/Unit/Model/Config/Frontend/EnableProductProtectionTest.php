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
   * @var EnableProductProtection
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
   * @var array
   */
    private $setDataCalls;


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
        ->onlyMethods(['getValue', 'isSetFlag'])
        ->getMock();

        $this->managerMock = $this->getMockBuilder(Manager::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['isEnabled'])
        ->getMock();

        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

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
        'getName',
        'setData'
        ])
        ->getMock();
        $this->elementMock
        ->method('getName')
        ->willReturn('test_name');

        $this->setDataCalls = [];
        $this->elementMock
        ->method('setData')
        ->willReturnCallback(function ($key, $value = null) {
            $this->setDataCalls[] = [$key, $value];
            return $this->elementMock;
        });
    }

  /**
   * @param array $expected list of [key, value] setData invocations, in order
   */
    private function assertSetDataCalls(array $expected): void
    {
        $this->assertEquals($expected, $this->setDataCalls);
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
        PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);
        $this->assertSetDataCalls([
        ['disabled', true],
        ['value', 0],
        ['comment', __(
            'Magento Product Protection V2 can only be enabled if Magento Product Protection V1 is completely disabled on all stores.'
        )],
        ]);
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
        PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);
        $this->assertSetDataCalls([
        ['disabled', true],
        ['value', 0],
        ]);
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
        PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);
        $this->assertSetDataCalls([]);
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
        PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
        $this->assertSetDataCalls([
        ['is_disable_inheritance', true],
        ]);
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
        PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
        $this->assertSetDataCalls([
        ['is_disable_inheritance', true],
        ]);
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
        PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
        $this->assertSetDataCalls([]);
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

      // The module calls getValue with a variable arg count (v1: path,'stores',storeId; v2: path
      // only). willReturnMap matches on exact passed args and resolves a mixed-arity map
      // differently across PHPUnit 9.6 vs 12.5, so dispatch on the path argument instead.
        $this->scopeConfigMock->expects($this->any())
        ->method('getValue')
        ->willReturnCallback(function ($path) use ($isV1Enabled, $isV2Enabled) {
            if ($path === 'warranty/enableExtend/enable') {
                return $isV1Enabled ? 1 : 0;
            }
            if ($path === Extend::ENABLE_EXTEND) {
                return $isV2Enabled ? 1 : 0;
            }
            return null;
        });

      // setup: Extend_Warranty disabled
        $this->managerMock->expects($this->any())
        ->method('isEnabled')
        ->with('Extend_Warranty')
        ->willReturn($isExtendWarrantyEnabled);
    }
}
