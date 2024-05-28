<?php

namespace Extend\Integration\Test\Unit\Model;

use Extend\Integration\Model\ShippingProtectionTotalRepository;
use PHPUnit\Framework\TestCase;

class ShippingProtectionTotalRepositoryTest extends TestCase
{

  /**
   * @var \Extend\Integration\Model\ShippingProtectionFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $shippingProtectionFactory;

  /**
   * @var \Extend\Integration\Model\ShippingProtectionTotalFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $shippingProtectionTotalFactory;

  /**
   * @var \Extend\Integration\Model\ResourceModel\ShippingProtectionTotal&\PHPUnit\Framework\MockObject\MockObject
   */
  private $shippingProtectionTotalResource;

  /**
   * @var \Extend\Integration\Model\ResourceModel\ShippingProtectionTotal\CollectionFactory&\PHPUnit\Framework\MockObject\Stub
   */
  private $shippingProtectionTotalCollection;

  /**
   * @var \Magento\Checkout\Model\Session&\PHPUnit\Framework\MockObject\Stub
   */
  private $checkoutSession;

  /**
   * @var \Extend\Integration\Model\ResourceModel\ShippingProtectionTotal\Collection&\PHPUnit\Framework\MockObject\Stub
   */
  private $collection;

  /**
   * @var \Extend\Integration\Model\ShippingProtectionTotal&\PHPUnit\Framework\MockObject\MockObject
   */
  private $total;

  /**
   * @var \Magento\Quote\Model\Quote&\PHPUnit\Framework\MockObject\Stub
   */
  private $quote;

  /**
   * @var \Magento\Sales\Model\Order&\PHPUnit\Framework\MockObject\MockObject
   */
  private $order;

  /**
   * @var \Magento\Sales\Api\Data\OrderExtension&\PHPUnit\Framework\MockObject\MockObject
   */
  private $extensionAttributes;

  /**
   * @var \Extend\Integration\Model\ShippingProtection&\PHPUnit\Framework\MockObject\Stub
   */
  private $shippingProtection;

  /**
   * @var ShippingProtectionTotalRepository
   */
  private $testSubject;

  protected function setUp(): void
  {
    // Create Stubs
    $this->shippingProtectionFactory = $this->createStub(\Extend\Integration\Model\ShippingProtectionFactory::class);
    $this->shippingProtectionTotalFactory = $this->createStub(\Extend\Integration\Model\ShippingProtectionTotalFactory::class);
    $this->shippingProtectionTotalResource = $this->createMock(\Extend\Integration\Model\ResourceModel\ShippingProtectionTotal::class);
    $this->shippingProtectionTotalCollection = $this->createStub(\Extend\Integration\Model\ResourceModel\ShippingProtectionTotal\CollectionFactory::class);
    $this->checkoutSession = $this->createStub(\Magento\Checkout\Model\Session::class);

    $this->collection = $this->createStub(\Extend\Integration\Model\ResourceModel\ShippingProtectionTotal\Collection::class);
    $this->total = $this->createMock(\Extend\Integration\Model\ShippingProtectionTotal::class);
    $this->quote = $this->createStub(\Magento\Quote\Model\Quote::class);
    $this->order = $this->createMock(\Magento\Sales\Model\Order::class);
    $this->extensionAttributes = $this->createMock(\Magento\Sales\Api\Data\OrderExtension::class);
    $this->shippingProtection = $this->createStub(\Extend\Integration\Model\ShippingProtection::class);
  }

  /**
   * @param float $price
   */
  protected function setupTest($populateTotalData = true)
  {
    // Set mock return values
    $this->shippingProtectionTotalCollection->method('create')->willReturn($this->collection);
    $this->shippingProtectionFactory->method('create')->willReturn($this->shippingProtection);
    $this->collection->method('addFieldToFilter')->willReturn($this->collection);
    $this->collection->method('load')->willReturn($this->collection);
    $this->collection->method('getFirstItem')->willReturn($this->total);
    $this->shippingProtectionTotalFactory->method('create')->willReturn($this->total);
    $this->checkoutSession->method('getQuote')->willReturn($this->quote);
    $this->quote->method('getId')->willReturn(123);
    $this->order->method('getExtensionAttributes')->willReturn($this->extensionAttributes);

    if ($populateTotalData) {
      $this->total->method('getData')->willReturn([
        'quote_id' => 123,
        'order_id' => 456,
        'shipping_protection_id' => 'abc',
        'shipping_protection_base_price' => 10.0,
        'shipping_protection_base_currency' => 'USD',
        'shipping_protection_price' => 10.0,
        'shipping_protection_currency' => 'USD',
        'shipping_protection_tax' => 1.0
      ]);
    } else {
      $this->total->method('getData')->willReturn([]);
    }

    // Create the test subject
    $this->testSubject = new ShippingProtectionTotalRepository(
      $this->shippingProtectionFactory,
      $this->shippingProtectionTotalFactory,
      $this->shippingProtectionTotalResource,
      $this->shippingProtectionTotalCollection,
      $this->checkoutSession
    );
  }

  public function testGet()
  {
    $this->setupTest();

    $res = $this->testSubject->get(123, 4);

    $this->assertEquals($this->total, $res);
  }

  public function testGetById()
  {
    $this->setupTest();

    $res = $this->testSubject->getById(123);

    $this->assertEquals($this->total, $res);
  }

  public function testSave()
  {
    $this->setupTest();

    $this->shippingProtectionTotalResource->expects($this->once())->method('save');

    $res = $this->testSubject->save(123, 456, 'abc', 10.0, 'USD', 10.0, 'USD', 1.0);

    $this->assertEquals($this->total, $res);
  }

  public function testSaveBySdkSpg()
  {
    $price = 10.0;
    $this->setupTest();

    $this->total->expects($this->once())->method('setShippingProtectionBasePrice')->with(0.0);
    $this->total->expects($this->once())->method('setShippingProtectionPrice')->with(0.0);
    $this->shippingProtectionTotalResource->expects($this->once())->method('save');

    $this->testSubject->saveBySdk('abc', $price, 'USD', $price, 'USD', 1.0, 'SPG');
  }

  public function testSaveBySdkNonSpg()
  {
    $price = 1000;
    $this->setupTest();

    $this->total->expects($this->once())->method('setShippingProtectionBasePrice')->with($price / 100);
    $this->total->expects($this->once())->method('setShippingProtectionPrice')->with($price / 100);
    $this->shippingProtectionTotalResource->expects($this->once())->method('save');

    $this->testSubject->saveBySdk('abc', $price, 'USD', $price, 'USD', 1.0, 'OPT_IN');
  }

  public function testDeleteById()
  {
    $this->setupTest();

    $this->shippingProtectionTotalResource->expects($this->once())->method('delete');

    $this->testSubject->deleteById(123);
  }

  public function testDelete()
  {
    $this->setupTest();

    $this->shippingProtectionTotalResource->expects($this->once())->method('delete');

    $this->testSubject->delete();
  }

  public function testGetAndSaturateExtensionAttributesWithNoSpTotal()
  {
    $this->setupTest(false);

    $this->extensionAttributes->expects($this->never())->method('setShippingProtection');
    $this->order->expects($this->never())->method('setExtensionAttributes');

    $this->testSubject->getAndSaturateExtensionAttributes(123, 4, $this->order);
  }

  public function testGetAndSaturateExtensionAttributesWithSpTotal()
  {
    $this->setupTest();

    $this->extensionAttributes->expects($this->once())->method('setShippingProtection');
    $this->order->expects($this->once())->method('setExtensionAttributes');

    $this->testSubject->getAndSaturateExtensionAttributes(123, 4, $this->order);
  }

  public function testSaveAndResaturateExtensionAttributeNegativeBase()
  {
    $this->setupTest();
    $this->shippingProtection->method('getBase')->willReturn(-10.0);

    $this->shippingProtectionTotalResource->expects($this->never())->method('save');
    $this->extensionAttributes->expects($this->never())->method('setShippingProtection');
    $this->order->expects($this->never())->method('setExtensionAttributes');

    $this->testSubject->saveAndResaturateExtensionAttribute($this->shippingProtection, $this->order, 4);
  }

  public function testSaveAndResaturateExtensionAttributeNegativePrice()
  {
    $this->setupTest();
    $this->shippingProtection->method('getBase')->willReturn(10.0);
    $this->shippingProtection->method('getBaseCurrency')->willReturn('USD');
    $this->shippingProtection->method('getPrice')->willReturn(-10.0);

    $this->shippingProtectionTotalResource->expects($this->never())->method('save');
    $this->extensionAttributes->expects($this->never())->method('setShippingProtection');
    $this->order->expects($this->never())->method('setExtensionAttributes');

    $this->testSubject->saveAndResaturateExtensionAttribute($this->shippingProtection, $this->order, 4);
  }

  public function testSaveAndResaturateExtensionAttributeNoEntityId()
  {
    $this->setupTest();
    $this->shippingProtection->method('getBase')->willReturn(10.0);
    $this->shippingProtection->method('getBaseCurrency')->willReturn('USD');
    $this->shippingProtection->method('getPrice')->willReturn(10.0);
    $this->shippingProtection->method('getCurrency')->willReturn('USD');
    $this->shippingProtection->method('getSpQuoteId')->willReturn('abc');
    $this->order->method('getEntityId')->willReturn(null);


    $this->shippingProtectionTotalResource->expects($this->never())->method('save');
    $this->extensionAttributes->expects($this->never())->method('setShippingProtection');
    $this->order->expects($this->never())->method('setExtensionAttributes');

    $this->testSubject->saveAndResaturateExtensionAttribute($this->shippingProtection, $this->order, 4);
  }

  public function testSaveAndResaturateExtensionAttribute()
  {
    $this->setupTest();
    $this->shippingProtection->method('getBase')->willReturn(10.0);
    $this->shippingProtection->method('getBaseCurrency')->willReturn('USD');
    $this->shippingProtection->method('getPrice')->willReturn(10.0);
    $this->shippingProtection->method('getCurrency')->willReturn('USD');
    $this->shippingProtection->method('getSpQuoteId')->willReturn('abc');
    $this->order->method('getEntityId')->willReturn(123);


    $this->shippingProtectionTotalResource->expects($this->once())->method('save');
    $this->extensionAttributes->expects($this->once())->method('setShippingProtection');
    $this->order->expects($this->once())->method('setExtensionAttributes');

    $this->testSubject->saveAndResaturateExtensionAttribute($this->shippingProtection, $this->order, 4);
  }

  public function testSaveByApi()
  {
    $price = 1000;
    $this->setupTest();

    $this->total->expects($this->once())->method('setShippingProtectionBasePrice')->with($price / 100);
    $this->total->expects($this->once())->method('setShippingProtectionPrice')->with($price / 100);
    $this->shippingProtectionTotalResource->expects($this->once())->method('save');

    $this->testSubject->saveByApi(123, 'def', $price, 'USD', $price, 'USD', 1.0);
  }
}
