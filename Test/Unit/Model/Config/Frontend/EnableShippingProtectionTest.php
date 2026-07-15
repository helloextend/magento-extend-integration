<?php

namespace Extend\Integration\Test\Unit\Model\Config\Frontend;

use Extend\Integration\Test\Utils\PHPUnitUtils;
use Extend\Integration\Service\Extend;
use Extend\Integration\Model\Config\Frontend\EnableShippingProtection;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\TestCase;

class EnableShippingProtectionTest extends TestCase
{

  /**
   * @var EnableShippingProtection
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
   * set up the test
   * @return void
   */
    protected function setUp(): void
    {

        $this->contextMock = $this->getMockBuilder(Context::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getValue', 'isSetFlag'])
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

        $this->model = new EnableShippingProtection(
            $this->contextMock,
            $this->scopeConfigMock,
            $this->managerMock,
            $this->storeManagerMock,
            [],
            $this->secureRendererMock
        );

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
    }

    public function testGetElementHtmlWithV2Enabled()
    {
        $this->scopeConfigMock->expects($this->once())
        ->method('getValue')
        ->with(Extend::ENABLE_EXTEND)
        ->willReturn(true);

        $this->elementMock->expects($this->never())
        ->method('setData');

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

        $setDataCalls = [];
        $this->elementMock->expects($this->exactly(2))
        ->method('setData')
        ->willReturnCallback(
            function ($key, $value) use (&$setDataCalls) {
                $setDataCalls[$key] = $value;
                return $this->elementMock;
            }
        );

        $this->elementMock->expects($this->once())
        ->method('getElementHtml');

        PHPUnitUtils::callMethod($this->model, '_getElementHtml', [$this->elementMock]);

        $this->assertSame(['disabled' => true, 'value' => 0], $setDataCalls);
    }

    public function testRenderInheritCheckboxWithV2Enabled()
    {
        $this->scopeConfigMock->expects($this->once())
        ->method('getValue')
        ->with(Extend::ENABLE_EXTEND)
        ->willReturn(true);

        $this->elementMock->expects($this->never())
        ->method('setData');

        PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
    }

    public function testRenderInheritCheckboxWithV2Disabled()
    {
        $this->scopeConfigMock->expects($this->once())
        ->method('getValue')
        ->with(Extend::ENABLE_EXTEND)
        ->willReturn(false);

        $this->elementMock->expects($this->once())
        ->method('setData')
        ->with('is_disable_inheritance', true);

        PHPUnitUtils::callMethod($this->model, '_renderInheritCheckbox', [$this->elementMock]);
    }
}
