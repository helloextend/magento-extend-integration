<?php

namespace Extend\Integration\Test\Unit\Model\Config\Backend;

use PHPUnit\Framework\TestCase;
use Extend\Integration\Model\Config\Backend\Enable;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Extend\Integration\Setup\Model\AttributeSetInstaller;
use Extend\Integration\Setup\Model\ProductInstaller;
use Magento\Framework\App\Config\Storage\WriterInterface;

class EnableTest extends TestCase
{

  protected const ENABLE_PRODUCT_PROTECTION_CONFIG_PATH = 'extend_plans/product_protection/enable';
  protected const ENABLE_SHIPPING_PROTECTION_CONFIG_PATH = 'extend_plans/shipping_protection/enable';
  protected const ENABLE_CART_BALANCING_CONFIG_PATH = 'extend_plans/product_protection/enable_cart_balancing';

  /**
   * @var Enable
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

    $this->model = new Enable(
      $this->contextMock,
      $this->registryMock,
      $this->scopeConfigMock,
      $this->typeListMock,
      $this->attributeSetInstallerMock,
      $this->productInstallerMock,
      $this->writerMock
    );
  }

  public function testAfterSaveWithV2Enabled()
  {
    $this->model->setValue(1);

    $this->writerMock->expects($this->never())
      ->method('save');

    $this->model->afterSave();
  }

  public function testAfterSaveWithV2Disabled()
  {
    $this->model->setValue(0);

    $this->writerMock->expects($this->exactly(3))
      ->method('save')
      ->withConsecutive(
        [EnableTest::ENABLE_SHIPPING_PROTECTION_CONFIG_PATH, 0],
        [EnableTest::ENABLE_PRODUCT_PROTECTION_CONFIG_PATH, 0],
        [EnableTest::ENABLE_CART_BALANCING_CONFIG_PATH, 0]
      );

    $this->model->afterSave();
  }
}
