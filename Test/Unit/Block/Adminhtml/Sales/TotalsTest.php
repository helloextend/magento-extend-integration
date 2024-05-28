<?php

namespace Extend\Integration\Test\Unit\Block\Adminhtml\Sales;

use Extend\Integration\Block\Adminhtml\Sales\Totals;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class TotalsTest extends TestCase
{

  /**
   * @var \Magento\Backend\Block\Template\Context&\PHPUnit\Framework\MockObject\Stub
   */
  private $context;

  /**
   * @var \Magento\Framework\View\LayoutInterface&\PHPUnit\Framework\MockObject\Stub
   */
  private $layout;

  /**
   * @var \Magento\Sales\Block\Order\Totals&\PHPUnit\Framework\MockObject\MockObject
   */
  private $block;

  /**
   * @var \Magento\Sales\Api\Data\OrderExtension&\PHPUnit\Framework\MockObject\Stub
   */
  private $extensionAttributes;

  /**
   * @var \Magento\Sales\Model\Order&\PHPUnit\Framework\MockObject\Stub
   */
  private $order;

  /**
   * @var \Extend\Integration\Model\ShippingProtection&\PHPUnit\Framework\MockObject\Stub
   */
  private $shippingProtection;

  /**
   * @var OrderExtensionFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $orderExtensionFactory;

  /**
   * @var InvoiceExtensionFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $invoiceExtension;

  /**
   * @var CreditmemoExtensionFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $creditmemoExtension;

  /**
   * @var OrderRepositoryInterface&\PHPUnit\Framework\MockObject\Stub
   */
  private $orderRepository;

  /**
   * @var Totals
   */
  private $testSubject;

  protected function setUp(): void
  {
    // Create Stubs
    $this->context = $this->createStub(Context::class);
    $this->layout = $this->createStub(\Magento\Framework\View\LayoutInterface::class);
    $this->block = $this->createMock(\Magento\Sales\Block\Order\Totals::class);
    $this->extensionAttributes = $this->createStub(\Magento\Sales\Api\Data\OrderExtension::class);
    // Add magic methods to the mock object
    $this->order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
      ->addMethods(
        ['getShippingProtection']
      )
      ->onlyMethods(
        ['getExtensionAttributes']
      )
      ->disableOriginalConstructor()
      ->getMock();
    $this->shippingProtection = $this->createStub(\Extend\Integration\Model\ShippingProtection::class);
    $this->orderExtensionFactory = $this->createStub(OrderExtensionFactory::class);
    $this->invoiceExtension = $this->createStub(InvoiceExtensionFactory::class);
    $this->creditmemoExtension = $this->createStub(CreditmemoExtensionFactory::class);
    $this->orderRepository = $this->createStub(OrderRepositoryInterface::class);
  }

  /**
   * @param float $price
   */
  protected function setupTest(float $price, $populateExtensionAttributes = true)
  {
    // Set mock return values
    $this->context->method('getLayout')->willReturn($this->layout);
    $this->layout->method('getParentName')->willReturn('parent_name');
    $this->layout->method('getBlock')->willReturn($this->block);
    $this->block->method('getSource')->willReturn($this->order);
    $this->order->method('getShippingProtection')->willReturn(null);
    $this->order->method('getExtensionAttributes')->willReturn($this->extensionAttributes);
    $this->shippingProtection->method('getPrice')->willReturn($price);

    if ($populateExtensionAttributes) {
      $this->extensionAttributes->method('getShippingProtection')->willReturn($this->shippingProtection);
    }

    // Create the test subject
    $this->testSubject = new Totals(
      $this->context,
      $this->orderExtensionFactory,
      $this->invoiceExtension,
      $this->creditmemoExtension,
      $this->orderRepository
    );
  }

  public function testGetShippingProtectionWithZeroPrice()
  {
    $price = 0.0;
    $this->setupTest($price);
    $res = $this->testSubject->getShippingProtection();
    $this->assertEquals($price, $res);
  }

  public function testGetShippingProtectionWithPositivePrice()
  {
    $price = 10.0;
    $this->setupTest($price);
    $res = $this->testSubject->getShippingProtection();
    $this->assertEquals($price, $res);
  }

  public function testGetShippingProtectionWithNoExtensionAttribute()
  {
    $price = 10.0;
    $this->setupTest($price, false);
    $res = $this->testSubject->getShippingProtection();
    $this->assertEquals(NULL, $res);
  }

  public function testGetShippingProtectionWithNegativePrice()
  {
    $price = -10.0;
    $this->setupTest($price);
    $res = $this->testSubject->getShippingProtection();
    $this->assertEquals(NULL, $res);
  }

  public function testInitTotalsWithZeroPrice()
  {
    $price = 0.0;
    $this->setupTest($price);
    $this->block->expects($this->once())->method('addTotal');
    $res = $this->testSubject->initTotals();
    $this->assertEquals($this->testSubject, $res);
  }

  public function testInitTotalsWithPositivePrice()
  {
    $price = 10.0;
    $this->setupTest($price);
    $this->block->expects($this->once())->method('addTotal');
    $res = $this->testSubject->initTotals();
    $this->assertEquals($this->testSubject, $res);
  }


  public function testInitTotalsWithNullShippingProtection()
  {
    $price = -10.0;
    $this->setupTest($price);
    $this->block->expects($this->never())->method('addTotal');
    $res = $this->testSubject->initTotals();
    $this->assertEquals($this->testSubject, $res);
  }

  public function testInitTotalsWithNoExtensionAttribute()
  {
    $price = 10.0;
    $this->setupTest($price, false);
    $this->block->expects($this->never())->method('addTotal');
    $res = $this->testSubject->initTotals();
    $this->assertEquals($this->testSubject, $res);
  }

  public function testInitTotalsWithNegativePrice()
  {
    $price = -10.0;
    $this->setupTest($price);
    $this->block->expects($this->never())->method('addTotal');
    $res = $this->testSubject->initTotals();
    $this->assertEquals($this->testSubject, $res);
  }
}
