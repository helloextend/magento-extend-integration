<?php

namespace Extend\Integration\Test\Unit\Block\Sales\Totals;

use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Block\Adminhtml\Sales\Order\Creditmemo\Totals;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Block\Template\Context;

class TotalsTest extends TestCase
{

  /**
   * @var \Magento\Backend\Block\Template\Context&\PHPUnit\Framework\MockObject\Stub
   */
  private $context;

  /**
   * @var \Magento\Sales\Api\Data\OrderExtensionFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $orderExtensionFactory;

  /**
   * @var \Magento\Sales\Api\Data\InvoiceExtensionFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $invoiceExtensionFactory;

  /**
   * @var \Magento\Sales\Api\Data\CreditmemoExtensionFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $creditmemoExtensionFactory;

  /**
   * @var \Magento\Sales\Api\OrderRepositoryInterface&\PHPUnit\Framework\MockObject\Stub
   */
  private $orderRepository;

  /**
   * @var \Magento\Sales\Model\Order\Creditmemo&\PHPUnit\Framework\MockObject\Stub
   */
  private $creditmemo;

 /**
   * @var \Magento\Framework\View\LayoutInterface&\PHPUnit\Framework\MockObject\Stub
   */
  private $layout;

  /**
   * @var \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals&\PHPUnit\Framework\MockObject\MockObject
   */
  private $block;

    /**
   * @var \Magento\Sales\Api\Data\OrderExtension&\PHPUnit\Framework\MockObject\Stub
   */
  private $extensionAttributes;

  /**
   * @var \Extend\Integration\Model\ShippingProtection&\PHPUnit\Framework\MockObject\Stub
   */
  private $shippingProtection;

  /**
   * @var Totals
   */
  private $testSubject;

  protected function setUp(): void
  {
    // Create Stubs
    $this->context = $this->createStub(Context::class);
    $this->orderExtensionFactory = $this->createStub(\Magento\Sales\Api\Data\OrderExtensionFactory::class);
    $this->invoiceExtensionFactory = $this->createStub(\Magento\Sales\Api\Data\InvoiceExtensionFactory::class);
    $this->creditmemoExtensionFactory = $this->createStub(\Magento\Sales\Api\Data\CreditmemoExtensionFactory::class);
    $this->orderRepository = $this->createStub(\Magento\Sales\Api\OrderRepositoryInterface::class);

    // Add magic methods to the mock object
    $this->creditmemo = $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
      ->addMethods(
        ['getShippingProtection', 'getSpgSpRemovedFromCreditMemo', 'getOmitSp']
      )
      ->onlyMethods(
        ['getExtensionAttributes']
      )
      ->disableOriginalConstructor()
      ->getMock();

    $this->layout = $this->createStub(\Magento\Framework\View\LayoutInterface::class);
    $this->block =  $this->getMockBuilder(\Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals::class)
      ->addMethods(
        ['getType']
      )
      ->onlyMethods(['getSource', 'addTotal'])
      ->disableOriginalConstructor()
      ->getMock();
    $this->extensionAttributes = $this->createStub(\Magento\Sales\Api\Data\OrderExtension::class);
    $this->shippingProtection = $this->createStub(\Extend\Integration\Model\ShippingProtection::class);
  }

  protected function setupTest($priceForGetShippingProtection = null, $priceForGetPrice = 0.0, $populateExtensionAttributes = true)
  {
    // Set mock return values
    $this->context->method('getLayout')->willReturn($this->layout);
    $this->layout->method('getParentName')->willReturn('parent_name');
    $this->layout->method('getBlock')->willReturn($this->block);
    $this->block->method('getSource')->willReturn($this->creditmemo);
    $this->block->method('getType')->willReturn(\Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals::class);
    $this->creditmemo->method('getShippingProtection')->willReturn($priceForGetShippingProtection);
    $this->creditmemo->method('getExtensionAttributes')->willReturn($this->extensionAttributes);
    $this->shippingProtection->method('getPrice')->willReturn($priceForGetPrice);
    if ($populateExtensionAttributes) {
      $this->extensionAttributes->method('getShippingProtection')->willReturn($this->shippingProtection);
    }

    // Create the test subject
    $this->testSubject = new Totals(
      $this->context,
      $this->orderExtensionFactory,
      $this->invoiceExtensionFactory,
      $this->creditmemoExtensionFactory,
      $this->orderRepository
    );
  }

  public function testGetShippingProtectionSourceSpNotNull()
  {
    $price = 0.0;
    $this->setupTest($price);
    $this->creditmemo->method('getShippingProtection')->willReturn($price);

    $this->creditmemo->expects($this->never())->method('getExtensionAttributes');
    $res = $this->testSubject->getShippingProtection();
    $this->assertEquals($price, $res);
  }

  public function testGetShippingProtectionNoExtensionAttribute()
  {
    $this->setupTest(NULL, 0.0, false);

    $this->extensionAttributes->expects($this->once())->method('getShippingProtection');
    $this->shippingProtection->expects($this->never())->method('getPrice');
    $res = $this->testSubject->getShippingProtection();
    $this->assertEquals(0, $res);
  }

  public function testGetShippingProtectionWithExtensionAttribute()
  {
    $price = 10.0;
    $this->setupTest(NULL, $price, true);
    $this->extensionAttributes->expects($this->once())->method('getShippingProtection');
    $this->shippingProtection->expects($this->exactly(2))->method('getPrice');
    $res = $this->testSubject->getShippingProtection();
    $this->assertEquals($price, $res);
  }

  public function testIsSpSpgNoExtensionAttribute()
  {
    $this->setupTest(NULL, 0.0, false);
    $this->shippingProtection->expects($this->never())->method('getOfferType');
    $res = $this->testSubject->isSpSpg();
    $this->assertEquals(false, $res);
  }

  public function testIsSpSpgNotSpg()
  {
    $this->setupTest(NULL, 0.0, true);
    $this->shippingProtection->method('getOfferType')->willReturn('not_spg');
    $this->shippingProtection->expects($this->once())->method('getOfferType');
    $res = $this->testSubject->isSpSpg();
    $this->assertEquals(false, $res);
  }

  public function testIsSpSpg()
  {
    $this->setupTest(NULL, 0.0, true);
    $this->shippingProtection->method('getOfferType')->willReturn(ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE);
    $this->shippingProtection->expects($this->once())->method('getOfferType');
    $res = $this->testSubject->isSpSpg();
    $this->assertEquals(true, $res);
  }

  public function testIsSpgSpRemovedFromCreditMemoFalse()
  {
    $this->setupTest(NULL, 0.0, true);
    $this->creditmemo->method('getSpgSpRemovedFromCreditMemo')->willReturn(false);
    $res = $this->testSubject->isSpgSpRemovedFromCreditMemo();
    $this->assertEquals(false, $res);
  }

  public function testIsSpgSpRemovedFromCreditMemoTrue()
  {
    $this->setupTest(NULL, 0.0, true);
    $this->creditmemo->method('getSpgSpRemovedFromCreditMemo')->willReturn(true);
    $res = $this->testSubject->isSpgSpRemovedFromCreditMemo();
    $this->assertEquals(true, $res);
  }

  public function testInitTotalsOmitSpTrue()
  {
    $this->setupTest();
    $this->creditmemo->method('getOmitSp')->willReturn(true);
    $this->block->expects($this->never())->method('addTotal');
    $this->testSubject->initTotals();
  }

  public function testInitTotals()
  {
    $this->setupTest();
    $this->creditmemo->method('getOmitSp')->willReturn(false);
    $this->block->expects($this->once())->method('addTotal');
    $this->testSubject->initTotals();
  }
}
