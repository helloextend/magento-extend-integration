<?php

namespace Extend\Integration\Test\Unit\Model\Config\Frontend;

use Extend\Integration\Test\Utils\PHPUnitUtils;
use Extend\Integration\Model\Config\Frontend\RecreatePPProduct;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\TestCase;

class RecreatePPProductTest extends TestCase
{

  /**
   * @var RecreatePPProduct|\PHPUnit\Framework\MockObject\MockObject
   */
  private $model;

  /**
   * @var AbstractElement|\PHPUnit\Framework\MockObject\MockObject
   */
  private $elementMock;

  /**
   * @var \Magento\Framework\Url|\PHPUnit\Framework\MockObject\MockObject
   */
  private $urlMock;

  /**
   * @var \Magento\Framework\Filesystem|\PHPUnit\Framework\MockObject\MockObject
   */
  private $filesystemMock;

  /**
   * @var Context|\PHPUnit\Framework\MockObject\MockObject
   */
  private $contextMock;

  /**
   * @var SecureHtmlRenderer|\PHPUnit\Framework\MockObject\MockObject
   */
  private $secureRendererMock;

  protected function setUp(): void
  {
    $this->urlMock = $this->createMock(\Magento\Framework\Url::class);
    $this->urlMock->method('getUrl')
      ->willReturn($this->returnArgument(0));

    $this->filesystemMock = $this->createMock(\Magento\Framework\Filesystem::class);
    $this->filesystemMock->method('getDirectoryRead')
      ->willReturn($this->createMock(\Magento\Framework\Filesystem\Directory\ReadInterface::class));

    $this->contextMock = $this->getMockBuilder(Context::class)
      ->disableOriginalConstructor()
      ->onlyMethods([
        'getUrlBuilder',
        'getEventManager',
        'getAppState',
        'getResolver',
        'getFilesystem',
        'getValidator',
        'getLogger'
      ])
      ->getMock();

    $this->contextMock
      ->method('getUrlBuilder')
      ->willReturn($this->urlMock);

    $this->contextMock
      ->method('getEventManager')
      ->willReturn($this->createMock(\Magento\Framework\Event\Manager::class));

    $this->contextMock
      ->method('getAppState')
      ->willReturn($this->createMock(\Magento\Framework\App\State::class));

    $this->contextMock
      ->method('getResolver')
      ->willReturn($this->createMock(\Magento\Framework\View\Element\Template\File\Resolver::class));

    $this->contextMock
      ->method('getFilesystem')
      ->willReturn($this->filesystemMock);

    $this->contextMock
      ->method('getValidator')
      ->willReturn($this->createMock(\Magento\Framework\View\Element\Template\File\Validator::class));

    $this->contextMock
      ->method('getLogger')
      ->willReturn($this->createMock(\Psr\Log\LoggerInterface::class));

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

    // this creates a partial mock of the model. the functions we're testing are unmocked,
    // but we can still mock the functions that they call and assert that they're called.
    $this->model = $this->getMockBuilder(RecreatePPProduct::class)
      ->setConstructorArgs([
        $this->contextMock,
        [],
        $this->secureRendererMock,
      ])
      ->onlyMethods([
        'addData',
        'getTemplate',
        'setTemplate'
      ])
      ->getMock();

    $this->elementMock = $this->getMockBuilder(AbstractElement::class)
      ->disableOriginalConstructor()
      ->addMethods(['getOriginalData'])
      ->getMock();
  }

  public function testPrepareLayoutWhenTemplateIsSet()
  {
    $this->model->expects($this->once())
      ->method('getTemplate')
      ->willReturn('some_template');
    $this->model->expects($this->never())
      ->method('setTemplate');
    PHPUnitUtils::callMethod($this->model, '_prepareLayout');
  }

  public function testPrepareLayoutWhenTemplateIsNotSet()
  {
    $this->model->expects($this->once())
      ->method('getTemplate')
      ->willReturn(null);
    $this->model->expects($this->once())
      ->method('setTemplate')
      ->with('Extend_Integration::system/config/recreate_pp_product.phtml');
    PHPUnitUtils::callMethod($this->model, '_prepareLayout');
  }

  public function testGetElementHtmlWhenButtonLabelIsNotSet()
  {
    $this->elementMock->expects($this->once())
      ->method('getOriginalData')
      ->willReturn([
        'button_url' => 'test_url',
      ]);
    $this->model->expects($this->never())
      ->method('addData');
    $this->model->_getElementHtml($this->elementMock);
  }
  public function testGetElementHtmlWhenButtonUrlIsNotSet()
  {
    $this->elementMock->expects($this->once())
      ->method('getOriginalData')
      ->willReturn([
        'button_label' => 'test_label'
      ]);
    $this->model->expects($this->never())
      ->method('addData');
    $this->model->_getElementHtml($this->elementMock);
  }
  public function testGetElementHtmlWhenButtonLabelAndButtonUrlAreSet()
  {
    $this->elementMock->expects($this->once())
      ->method('getOriginalData')
      ->willReturn([
        'button_label' => 'test_label',
        'button_url' => 'test_url'
      ]);
    $this->model->expects($this->once())
      ->method('addData')
      ->with([
        'button_label' => 'test_label',
        'html_id' => 'recreate_pp_product',
        'button_url' => 'test_url'
      ]);
    $this->model->_getElementHtml($this->elementMock);
  }
}
