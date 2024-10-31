<?php

namespace Extend\Integration\Test\Unit\Model\Sales\Order\Invoice\Total;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Extend\Integration\Model\Sales\Order\Invoice\Total\ShippingProtection;
use Extend\Integration\Model\ShippingProtectionFactory;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Model\ShippingProtectionTotal;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Extend\Integration\Model\ShippingProtection as BaseShippingProtectionModel;
use Magento\Sales\Api\Data\InvoiceExtensionInterface;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;
use Extend\Integration\Test\Unit\Mock\MagicMock;

class ShippingProtectionTest extends TestCase
{

  /**
   * @var float
   */
  private $shippingProtectionPrice;

  /**
   * @var float
   */
  private $shippingProtectionBasePrice;

  /**
   * @var float
   */
  private $invoiceGrandTotal;

  /**
   * @var float
   */
  private $invoiceBaseGrandTotal;

  /**
   * @var ShippingProtection
   */
  private $testSubject;

  /**
   * @var ShippingProtectionTotalRepositoryInterface | MockObject
   */
  private $shippingProtectionTotalRepositoryMock;

  /**
   * @var ShippingProtectionTotal | MockObject
   */
  private $shippingProtectionTotalMock;

  /**
   * @var ShippingProtectionFactory | MockObject
   */
  private $shippingProtectionFactoryMock;

  /**
   * @var Invoice | MockObject
   */
  private $invoiceMock;

  /**
   * @var Order | MockObject
   */
  private $orderMock;

  /**
   * @var InvoiceCollection | MockObject
   */
  private $invoiceCollectionMock;

  /**
   * @var BaseShippingProtectionModel | MockObject
   */
  private $baseShippingProtectionModelMock;

  /**
   * @var InvoiceExtensionInterface | MockObject
   */
  private $extensionAttributesMock;

  /**
   * @var InvoiceItem | MockObject
   */
  private $invoiceItemMock;

  /**
   * @var OrderItem | MockObject
   */
  private $orderItemMock;

  /**
   * @var Collection | MockObject
   */
  private $invoiceItemsCollectionMock;

  protected function setUp(): void
  {
    // set primitive test values
    $this->shippingProtectionPrice = 123.45;
    $this->shippingProtectionBasePrice = 234.56;
    $this->invoiceGrandTotal = 900.00;
    $this->invoiceBaseGrandTotal = 1000.00;

    // create mock constructor args for the tested class
    $this->shippingProtectionTotalRepositoryMock = $this->createStub(ShippingProtectionTotalRepositoryInterface::class);
    $this->shippingProtectionTotalMock = $this->createStub(ShippingProtectionTotal::class);
    $this->shippingProtectionTotalMock->method('getId')->willReturn(123);
    $this->shippingProtectionTotalMock->method('getShippingProtectionBasePrice')->willReturn($this->shippingProtectionBasePrice);
    $this->shippingProtectionFactoryMock = $this->createStub(ShippingProtectionFactory::class);
    $this->baseShippingProtectionModelMock = $this->createStub(BaseShippingProtectionModel::class);
    $this->baseShippingProtectionModelMock->method('getBase')->willReturn($this->shippingProtectionBasePrice);
    $this->baseShippingProtectionModelMock->method('getPrice')->willReturn($this->shippingProtectionPrice);
    $this->shippingProtectionFactoryMock
      ->method('create')
      ->willReturn($this->baseShippingProtectionModelMock);

    // create the class to test
    $this->testSubject = new ShippingProtection(
      $this->shippingProtectionTotalRepositoryMock,
      $this->shippingProtectionFactoryMock
    );

    // create arguments for tested method(s)
    $this->invoiceMock = $this->getMockBuilder(Invoice::class)
      ->disableOriginalConstructor()
      ->onlyMethods([
        'getOrder',
        'getOrderId',
        'getItemsCollection',
        'getAllItems',
        'getExtensionAttributes',
        'setGrandTotal',
        'setBaseGrandTotal',
        'getGrandTotal',
        'getBaseGrandTotal'
      ])
      ->addMethods([
        'setShippingProtection',
        'setBaseShippingProtection',
        'getShippingProtection',
        'getBaseShippingProtection',
        'setOmitSp'
      ])
      ->getMock();
    $this->extensionAttributesMock = $this->createMock(MagicMock::class);
    $this->invoiceMock->method('getExtensionAttributes')
      ->willReturn($this->extensionAttributesMock);
    $this->invoiceMock->method('getGrandTotal')->willReturn($this->invoiceGrandTotal);
    $this->invoiceMock->method('getBaseGrandTotal')->willReturn($this->invoiceBaseGrandTotal);

    // additional setup needed to cover the permutations in the test cases below
    $this->orderMock = $this->createStub(Order::class);
    $this->invoiceCollectionMock = $this->createStub(InvoiceCollection::class);
    $this->invoiceItemsCollectionMock = $this->createStub(Collection::class);
    $this->invoiceItemMock = $this->createStub(InvoiceItem::class);
    $this->orderItemMock = $this->createStub(OrderItem::class);
  }

  /* =================================================================================================== */
  /* ============================================== tests ============================================== */
  /* =================================================================================================== */

  // test collect when invoice extension attributes has shipping protection and invoice has no order and invoice has a single, virtual item
  public function testCollectWhenInvoiceExtensionAttributesHasShippingProtectionAndInvoiceHasNoOrderAndInvoiceHasASingleVirtualItem()
  {
    // configure test conditions
    $this->setTestConditions([
      'invoiceExtensionAttributesHasShippingProtection' => true,
      'invoiceHasOrder' => false,
      'invoiceHasSingleVirtualItem' => true,
    ]);
    // set expectations
    $this->expectNothingToHappen();
    // run the test function
    $this->testSubject->collect($this->invoiceMock);
  }

  // test collect when invoice extension attributes has shipping protection and invoice has no order and invoice has a single, non-virtual item
  public function testCollectWhenInvoiceExtensionAttributesHasShippingProtectionAndInvoiceHasNoOrderAndInvoiceHasASingleNonVirtualItem()
  {
    // configure test conditions
    $this->setTestConditions([
      'invoiceExtensionAttributesHasShippingProtection' => true,
      'invoiceHasOrder' => false,
      'invoiceHasSingleNonVirtualItem' => true,
    ]);
    $this->invoiceMock->method('getShippingProtection')->willReturn($this->shippingProtectionPrice);
    $this->invoiceMock->method('getBaseShippingProtection')->willReturn($this->shippingProtectionBasePrice);

    // set expectations
    $this->expectInvoiceValuesToBeUpdatedWithNonZeroValues();
    // run the test function
    $this->testSubject->collect($this->invoiceMock);
  }

  // test collect when invoice extension attributes has shipping protection and invoice is in shipping protection total repository already
  public function testCollectWhenInvoiceExtensionAttributesHasShippingProtectionAndInvoiceIsInShippingProtectionTotalRepositoryAlready()
  {
    // configure test conditions
    $this->setTestConditions([
      'invoiceExtensionAttributesHasShippingProtection' => true,
      'invoiceHasOrder' => true,
      'invoiceIsInShippingProtectionTotalRepositoryAlready' => true,
    ]);
    // set expectations
    $this->invoiceMock->expects($this->exactly(2))->method('setOmitSp')->withConsecutive([false], [true]);
    // run the test function
    $this->testSubject->collect($this->invoiceMock);
  }

  // test collect when invoice extension attributes has shipping protection and invoice is in shipping protection total repository already for $0 SPG
  public function testCollectWhenInvoiceExtensionAttributesHasShippingProtectionAndInvoiceIsInShippingProtectionTotalRepositoryAlreadySpg()
  {
    // configure test conditions
    $this->setTestConditions([
      'invoiceExtensionAttributesHasShippingProtection' => true,
      'invoiceHasOrder' => true,
      'invoiceIsInShippingProtectionTotalRepositoryAlready' => true,
    ]);
    $this->shippingProtectionTotalMock->method('getShippingProtectionBasePrice')->willReturn(0.0);
    $this->shippingProtectionTotalMock->method('getOfferType')->willReturn(ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE);
    // set expectations
    $this->invoiceMock->expects($this->exactly(2))->method('setOmitSp')->withConsecutive([false], [true]);
    // run the test function
    $this->testSubject->collect($this->invoiceMock);
  }


  // test collect when invoice extension attributes has shipping protection and invoice has an order and order has a single, virtual item
  public function testCollectWhenInvoiceExtensionAttributesHasShippingProtectionAndInvoiceHasAnOrderAndOrderHasASingleVirtualItem()
  {
    // configure test conditions
    $this->setTestConditions([
      'invoiceExtensionAttributesHasShippingProtection' => true,
      'invoiceHasOrder' => true,
      'invoiceIsInShippingProtectionTotalRepositoryAlready' => true,
      'invoiceHasSingleVirtualItem' => true,
    ]);
    // set expectations
    $this->invoiceMock->expects($this->exactly(2))->method('setOmitSp')->withConsecutive([false], [true]);
    // run the test function
    $this->testSubject->collect($this->invoiceMock);
  }

  // test collect when invoice extension attributes has shipping protection and invoice has an order and order has a single, non-virtual item
  public function testCollectWhenInvoiceExtensionAttributesHasShippingProtectionAndInvoiceHasAnOrderAndOrderHasASingleNonVirtualItem()
  {
    // configure test conditions
    $this->setTestConditions([
      'invoiceExtensionAttributesHasShippingProtection' => true,
      'invoiceHasOrder' => true,
      'invoiceHasSingleNonVirtualItem' => true,
    ]);
    $this->invoiceMock->method('getShippingProtection')->willReturn($this->shippingProtectionPrice);
    $this->invoiceMock->method('getBaseShippingProtection')->willReturn($this->shippingProtectionBasePrice);
    $this->orderMock->method('getInvoiceCollection')->willReturn($this->invoiceCollectionMock);
    $this->invoiceCollectionMock->method('getAllIds')->willReturn([123]);
    $this->invoiceItemMock->method('getQty')->willReturn(3);
    $this->orderItemMock->method('getIsVirtual')->willReturn('0');
    // set expectations
    $this->expectInvoiceValuesToBeUpdatedWithNonZeroValues();
    // run the test function
    $this->testSubject->collect($this->invoiceMock);
  }

  /* =================================================================================================== */
  /* ========================== helper methods for setting up test conditions ========================== */
  /* =================================================================================================== */

  /**
   * @param array $conditions
   * 1. invoiceExtensionAttributesHasShippingProtection
   * 2. invoiceHasOrder
   * 3. invoiceIsInShippingProtectionTotalRepositoryAlready
   * 4. invoiceHasSingleVirtualItem
   * 5. invoiceHasSingleNonVirtualItem
   * @return void
   */
  private function setTestConditions(
    array $conditions
  ) {
    $this->setInvoiceExtensionAttributesHasShippingProtection(
      $conditions['invoiceExtensionAttributesHasShippingProtection'] ?? false
    );
    $this->setInvoiceHasOrder(
      $conditions['invoiceHasOrder'] ?? false
    );
    $this->setInvoiceIsInShippingProtectionTotalRepositoryAlready(
      $conditions['invoiceIsInShippingProtectionTotalRepositoryAlready'] ?? false
    );
    $this->setInvoiceHasSingleVirtualItem(
      $conditions['invoiceHasSingleVirtualItem'] ?? false
    );
    $this->setInvoiceHasSingleNonVirtualItem(
      $conditions['invoiceHasSingleNonVirtualItem'] ?? false
    );
  }

  private function setInvoiceExtensionAttributesHasShippingProtection(bool $condition)
  {
    if (isset($condition) && $condition) {
      $this->extensionAttributesMock->method('getShippingProtection')->willReturn($this->baseShippingProtectionModelMock);
    } elseif (isset($condition)) {
      $this->extensionAttributesMock->method('getShippingProtection')->willReturn(null);
    }
  }

  private function setInvoiceHasOrder(bool $condition)
  {
    if (isset($condition) && $condition) {
      $this->invoiceMock->method('getOrder')->willReturn($this->orderMock);
      $this->invoiceMock->method('getOrderId')->willReturn(123);
    }
  }

  private function setInvoiceIsInShippingProtectionTotalRepositoryAlready(bool $condition)
  {
    if (isset($condition) && $condition) {
      $this->orderMock->method('getInvoiceCollection')->willReturn($this->invoiceCollectionMock);
      $this->invoiceCollectionMock->method('getAllIds')->willReturn([123]);
      $this->shippingProtectionTotalRepositoryMock->method('get')->willReturn($this->shippingProtectionTotalMock);
    }
  }

  private function setInvoiceHasSingleVirtualItem(bool $condition)
  {
    if (isset($condition) && $condition) {
      $this->invoiceMock->method('getAllItems')->willReturn([$this->invoiceItemMock]);
      $this->invoiceItemMock->method('getOrderItem')->willReturn($this->orderItemMock);
      $this->orderItemMock->method('getIsVirtual')->willReturn('1');
    }
  }

  private function setInvoiceHasSingleNonVirtualItem(bool $condition)
  {
    if (isset($condition) && $condition) {
      $this->invoiceMock->method('getAllItems')->willReturn([$this->invoiceItemMock]);
      $this->invoiceItemMock->method('getOrderItem')->willReturn($this->orderItemMock);
      $this->orderItemMock->method('getIsVirtual')->willReturn('0');
    }
  }

  /* =================================================================================================== */
  /* ============================== helper methods for validating results ============================== */
  /* =================================================================================================== */

  private function expectNothingToHappen()
  {
    $this->invoiceMock->expects($this->never())->method('setShippingProtection');
    $this->invoiceMock->expects($this->never())->method('setBaseShippingProtection');
    $this->invoiceMock->expects($this->never())->method('setGrandTotal');
    $this->invoiceMock->expects($this->never())->method('setBaseGrandTotal');
    $this->shippingProtectionFactoryMock->expects($this->never())->method('create');
  }

  private function expectInvoiceValuesToBeUpdatedWithNonZeroValues()
  {
    $this->invoiceMock->expects($this->once())->method('setShippingProtection')->with($this->shippingProtectionPrice);
    $this->invoiceMock->expects($this->once())->method('setBaseShippingProtection')->with($this->shippingProtectionBasePrice);
    $this->invoiceMock->expects($this->once())->method('setGrandTotal')->with($this->invoiceGrandTotal + $this->shippingProtectionPrice);
    $this->invoiceMock->expects($this->once())->method('setBaseGrandTotal')->with($this->invoiceBaseGrandTotal + $this->shippingProtectionBasePrice);
  }
}
