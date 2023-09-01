<?php

namespace Extend\Integration\Test\Unit\Model\Config\Backend;

use PHPUnit\Framework\TestCase;
use Extend\Integration\Model\Config\Backend\V1enable;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class V1enableTest extends TestCase
{

  protected const ENABLE_PRODUCT_PROTECTION_CONFIG_PATH = 'extend_plans/product_protection/enable';

  /**
   * @var V1enable
   */
  protected $model;

  /**
   * @var Context|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $contextMock;

  /**
   * @var Registry|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $registryMock;

  /**
   * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $scopeConfigMock;

  /**
   * @var TypeListInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typeListMock;

  /**
   * @var WriterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $writerMock;

  protected function setUp(): void
  {

    $this->writerMock = $this->getMockBuilder(WriterInterface::class)
      ->disableOriginalConstructor()
      ->getMock();


    $this->contextMock = $this->getMockBuilder(Context::class)
      ->disableOriginalConstructor()
      ->onlyMethods([
        'getEventDispatcher'
      ])
      ->getMock();
    $this->contextMock
      ->method('getEventDispatcher')
      ->willReturn($this->createMock(\Magento\Framework\Event\Manager::class));

    $this->registryMock = $this->createMock(Registry::class);

    $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

    $this->typeListMock = $this->createMock(TypeListInterface::class);

    $this->writerMock = $this->getMockBuilder(WriterInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->model = new V1enable(
      $this->contextMock,
      $this->registryMock,
      $this->scopeConfigMock,
      $this->typeListMock,
      $this->writerMock
    );
  }

  public function testAfterSaveWithV1Enabled()
  {
    $this->model->setValue(1);

    $this->writerMock->expects($this->once())
      ->method('save')
      ->with(V1enableTest::ENABLE_PRODUCT_PROTECTION_CONFIG_PATH, 0);

    $this->model->afterSave();
  }

  public function testAfterSaveWithV1Disabled()
  {
    $this->model->setValue(0);

    $this->writerMock->expects($this->never())
      ->method('save');

    $this->model->afterSave();
  }
}
