<?php

namespace Extend\Integration\Test\Unit\Model\Config\Backend;

use PHPUnit\Framework\TestCase;
use Extend\Integration\Model\Config\Backend\EnableProductProtection;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Extend\Integration\Setup\Model\AttributeSetInstaller;
use Extend\Integration\Setup\Model\ProductInstaller;
use Magento\Framework\App\Config\Storage\WriterInterface;

class EnableProductProtectionTest extends TestCase
{

  /**
   * @var EnableProductProtection
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
   * @var AttributeSetInstaller|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $attributeSetInstallerMock;

  /**
   * @var ProductInstaller|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $productInstallerMock;

  /**
   * @var WriterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $writerMock;

  protected function setUp(): void
  {

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

    $this->attributeSetInstallerMock = $this->getMockBuilder(AttributeSetInstaller::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->productInstallerMock = $this->getMockBuilder(ProductInstaller::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->model = new EnableProductProtection(
      $this->contextMock,
      $this->registryMock,
      $this->scopeConfigMock,
      $this->typeListMock,
      $this->attributeSetInstallerMock,
      $this->productInstallerMock,
      $this->writerMock
    );
  }

  public function testAfterSaveWithPPV2Enabled()
  {
    $this->model->setValue(1);

    $this->attributeSetInstallerMock->expects($this->once())
      ->method('createAttributeSet');

    $this->productInstallerMock->expects($this->once())
      ->method('createProduct');

    $this->model->afterSave();
  }

  public function testAfterSaveWithPPV2Disabled()
  {
    $this->model->setValue(0);

    $this->attributeSetInstallerMock->expects($this->never())
      ->method('createAttributeSet');

    $this->productInstallerMock->expects($this->never())
      ->method('createProduct');

    $this->model->afterSave();
  }
}
