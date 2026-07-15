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

  /**
   * @var array<string, mixed>
   */
    private $creditmemoData = [];

    protected function setUp(): void
    {
      // Create Stubs
        $this->context = $this->createStub(Context::class);
        $this->orderExtensionFactory = $this->createStub(\Magento\Sales\Api\Data\OrderExtensionFactory::class);
        $this->invoiceExtensionFactory = $this->createStub(\Magento\Sales\Api\Data\InvoiceExtensionFactory::class);
        $this->creditmemoExtensionFactory = $this->createStub(\Magento\Sales\Api\Data\CreditmemoExtensionFactory::class);
        $this->orderRepository = $this->createStub(\Magento\Sales\Api\OrderRepositoryInterface::class);

        $this->creditmemo = $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
        ->onlyMethods(
            ['getExtensionAttributes', 'getData']
        )
        ->disableOriginalConstructor()
        ->getMock();

        $this->layout = $this->createStub(\Magento\Framework\View\LayoutInterface::class);
        $this->block =  $this->getMockBuilder(\Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals::class)
        ->onlyMethods(['getSource', 'addTotal', 'getData'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->extensionAttributes = $this->createMock(\Magento\Sales\Api\Data\OrderExtension::class);
        $this->shippingProtection = $this->createMock(\Extend\Integration\Model\ShippingProtection::class);
    }

    protected function setupTest($priceForGetShippingProtection = null, $priceForGetPrice = 0.0, $populateExtensionAttributes = true)
    {
      // Set mock return values
        $this->context->method('getLayout')->willReturn($this->layout);
        $this->layout->method('getParentName')->willReturn('parent_name');
        $this->layout->method('getBlock')->willReturn($this->block);
        $this->block->method('getSource')->willReturn($this->creditmemo);
        $this->block->method('getData')->with('type')
        ->willReturn(\Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals::class);
        $this->creditmemoData['shipping_protection'] = $priceForGetShippingProtection;
        $this->creditmemo->method('getData')->willReturnCallback(
            function ($key) {
                return $this->creditmemoData[$key] ?? null;
            }
        );
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
        $this->creditmemoData['shipping_protection'] = $price;

        $this->creditmemo->expects($this->never())->method('getExtensionAttributes');
        $res = $this->testSubject->getShippingProtection();
        $this->assertEquals($price, $res);
    }

    public function testGetShippingProtectionNoExtensionAttribute()
    {
        $this->setupTest(null, 0.0, false);

        $this->extensionAttributes->expects($this->once())->method('getShippingProtection');
        $this->shippingProtection->expects($this->never())->method('getPrice');
        $res = $this->testSubject->getShippingProtection();
        $this->assertEquals(0, $res);
    }

    public function testGetShippingProtectionWithExtensionAttribute()
    {
        $price = 10.0;
        $this->setupTest(null, $price, true);
        $this->extensionAttributes->expects($this->once())->method('getShippingProtection');
        $this->shippingProtection->expects($this->exactly(2))->method('getPrice');
        $res = $this->testSubject->getShippingProtection();
        $this->assertEquals($price, $res);
    }

    public function testIsSpSpgNoExtensionAttribute()
    {
        $this->setupTest(null, 0.0, false);
        $this->shippingProtection->expects($this->never())->method('getOfferType');
        $res = $this->testSubject->isSpSpg();
        $this->assertEquals(false, $res);
    }

    public function testIsSpSpgNotSpg()
    {
        $this->setupTest(null, 0.0, true);
        $this->shippingProtection->method('getOfferType')->willReturn('not_spg');
        $this->shippingProtection->expects($this->once())->method('getOfferType');
        $res = $this->testSubject->isSpSpg();
        $this->assertEquals(false, $res);
    }

    public function testIsSpSpg()
    {
        $this->setupTest(null, 0.0, true);
        $this->shippingProtection->method('getOfferType')->willReturn(ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE);
        $this->shippingProtection->expects($this->once())->method('getOfferType');
        $res = $this->testSubject->isSpSpg();
        $this->assertEquals(true, $res);
    }

    public function testIsSpgSpRemovedFromCreditMemoFalse()
    {
        $this->setupTest(null, 0.0, true);
        $this->creditmemoData['spg_sp_removed_from_credit_memo'] = false;
        $res = $this->testSubject->isSpgSpRemovedFromCreditMemo();
        $this->assertEquals(false, $res);
    }

    public function testIsSpgSpRemovedFromCreditMemoTrue()
    {
        $this->setupTest(null, 0.0, true);
        $this->creditmemoData['spg_sp_removed_from_credit_memo'] = true;
        $res = $this->testSubject->isSpgSpRemovedFromCreditMemo();
        $this->assertEquals(true, $res);
    }

    public function testInitTotalsOmitSpTrue()
    {
        $this->setupTest();
        $this->creditmemoData['omit_sp'] = true;
        $this->block->expects($this->never())->method('addTotal');
        $this->testSubject->initTotals();
    }

    public function testInitTotals()
    {
        $this->setupTest();
        $this->creditmemoData['omit_sp'] = false;
        $this->block->expects($this->once())->method('addTotal');
        $this->testSubject->initTotals();
    }
}
