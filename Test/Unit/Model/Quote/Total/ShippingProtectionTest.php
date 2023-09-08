<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Model\Quote\Total;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Model\ShippingProtection as BaseShippingProtectionModel;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Api\Data\CartExtension;
use Magento\Quote\Model\Quote\Address;

use Extend\Integration\Model\Quote\Total\ShippingProtection;

class ShippingProtectionTest extends TestCase
{
  /**
   * @var SerializerInterface|MockObject
   */
  private $serializerMock;

  /**
   * @var CartExtensionFactory|MockObject
   */
  private $cartExtensionFactoryMock;

  /**
   * @var CartExtension|MockObject
   */
  private $cartExtensionMock;

  /**
   * @var ShippingProtectionTotalRepositoryInterface|MockObject
   */
  private $shippingProtectionTotalRepositoryMock;

  /**
   * @var Quote|MockObject
   */
  private $quoteMock;

  /**
   * @var ShippingAssignmentInterface|MockObject
   */
  private $shippingAssignmentMock;

  /**
   * @var ShippingInterface|MockObject
   */
  private $shippingMock;

  /**
   * @var Total
   */
  private $total;

  /**
   * @var ShippingProtection|MockObject
   */
  private $shippingProtectionMock;

  /**
   * @var ShippingProtection
   */
  private $testSubject;

  /**
   * @var float
   */
  private $shippingProtectionPrice = 123.45;

  /**
   * @var float
   */
  private $shippingProtectionBasePrice = 543.21;

  protected function setUp(): void
  {
    $this->serializerMock = $this->createMock(SerializerInterface::class);
    $this->cartExtensionFactoryMock = $this->getMockBuilder(CartExtensionFactory::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['create'])
      ->getMock();
    $this->cartExtensionMock = $this->getMockBuilder(CartExtension::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getShippingProtection'])
      ->getMock();
    $this->shippingProtectionTotalRepositoryMock = $this->createMock(ShippingProtectionTotalRepositoryInterface::class);
    $this->quoteMock = $this->getMockBuilder(Quote::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getExtensionAttributes'])
      ->getMock();
    $this->shippingAssignmentMock = $this->getMockBuilder(ShippingAssignmentInterface::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getShipping'])
      ->getMockForAbstractClass();
    $this->shippingMock = $this->getMockBuilder(ShippingInterface::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getAddress'])
      ->getMockForAbstractClass();
    $this->shippingMock
      ->method('getAddress')
      ->willReturn($this->createMock(Address::class));
    $this->shippingAssignmentMock->method('getShipping')
      ->willReturn($this->shippingMock);
    $this->total = new Total();

    $this->shippingProtectionMock = $this->getMockBuilder(BaseShippingProtectionModel::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getPrice', 'getBase'])
      ->getMock();

    $this->testSubject = new ShippingProtection(
      $this->shippingProtectionTotalRepositoryMock,
      $this->serializerMock,
      $this->cartExtensionFactoryMock
    );

    $this->testSubject->setCode('shipping_protection');
  }

  public function testCollectWhenExtensionAttributesAreNull()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => false
    ]);
    $this->cartExtensionFactoryMock
      ->expects($this->once())
      ->method('create')
      ->willReturn($this->cartExtensionMock);
    // test and assert
    $this->runCollect();
    $this->assertEquals(0, $this->total->getBaseTotalAmount('shipping_protection'));
  }

  public function testCollectWhenShippingProtectionIsNull()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => true,
      'shippingProtectionExists' => false
    ]);
    // test and assert
    $this->runCollect();
    $this->assertEquals(0, $this->total->getBaseTotalAmount('shipping_protection'));
  }

  public function testCollectWhenShippingProtectionPriceIsZero()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => true,
      'shippingProtectionExists' => true,
      'shippingProtectionHasNonZeroPrice' => false
    ]);
    // test and assert
    $this->runCollect();
    $this->assertEquals(0, $this->total->getBaseTotalAmount('shipping_protection'));
  }

  public function testCollectWhenShippingProtectionPriceIsGreaterThanZeroAndShippingProtectionBaseIsZero()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => true,
      'shippingProtectionExists' => true,
      'shippingProtectionHasNonZeroPrice' => true,
      'shippingProtectionHasNonZeroBasePrice' => false
    ]);
    // test and assert
    $this->runCollect();
    $this->assertEquals($this->shippingProtectionPrice, $this->total->getBaseTotalAmount('shipping_protection'));
    $this->assertEquals($this->shippingProtectionPrice, $this->total->getTotalAmount('shipping_protection'));
  }

  public function testCollectWhenShippingProtectionPriceIsGreaterThanZeroAndShippingProtectionBaseIsGreaterThanZero()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => true,
      'shippingProtectionExists' => true,
      'shippingProtectionHasNonZeroPrice' => true,
      'shippingProtectionHasNonZeroBasePrice' => true
    ]);
    // test and assert
    $this->runCollect();
    $this->assertEquals($this->shippingProtectionBasePrice, $this->total->getBaseTotalAmount('shipping_protection'));
    $this->assertEquals($this->shippingProtectionPrice, $this->total->getTotalAmount('shipping_protection'));
  }

  public function testFetchWhenExtensionAttributesAreNull()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => false
    ]);
    $this->cartExtensionFactoryMock
      ->expects($this->once())
      ->method('create')
      ->willReturn($this->cartExtensionMock);
    // test and assert
    $this->assertEquals([], $this->runFetch());
  }

  public function testFetchWhenShippingProtectionIsNull()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => true,
      'shippingProtectionExists' => false
    ]);
    // test and assert
    $this->assertEquals([], $this->runFetch());
  }

  public function testFetchWhenShippingProtectionPriceIsZero()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => true,
      'shippingProtectionExists' => true,
      'shippingProtectionHasNonZeroPrice' => false
    ]);
    // test and assert
    $this->assertEquals([], $this->runFetch());
  }

  public function testFetchWhenShippingProtectionPriceIsGreaterThanZero()
  {
    // set up
    $this->setTestConditions([
      'extensionAttributesExist' => true,
      'shippingProtectionExists' => true,
      'shippingProtectionHasNonZeroPrice' => true
    ]);
    // test and assert
    $result = $this->runFetch();
    $this->assertEquals('shipping_protection', $result['code']);
    $this->assertEquals($this->shippingProtectionPrice, $result['value']);
  }

  /* =================================================================================================== */
  /* ========================== helper methods for setting up test conditions ========================== */
  /* =================================================================================================== */

  private function runCollect(): ShippingProtection
  {
    return $this->testSubject->collect($this->quoteMock, $this->shippingAssignmentMock, $this->total);
  }

  private function runFetch(): array
  {
    return $this->testSubject->fetch($this->quoteMock, $this->total);
  }

  /**
   * helper function to set up the test conditions for the above tests.
   *
   * @param array $conditions - array of booleans, in the order:
   * 1. extensionAttributesExist
   * 2. shippingProtectionExists
   * 3. shippingProtectionHasNonZeroPrice
   * 4. shippingProtectionHasNonZeroBasePrice
   * @return void
   */
  private function setTestConditions(
    array $conditions
  ) {
    if (isset($conditions['extensionAttributesExist']))
      $conditions['extensionAttributesExist'] ? $this->setExtensionAttributes() : $this->setExtensionAttributesNull();
    if (isset($conditions['shippingProtectionExists']))
      $conditions['shippingProtectionExists'] ? $this->setShippingProtection() : $this->setShippingProtectionNull();
    if (isset($conditions['shippingProtectionHasNonZeroPrice']))
      $conditions['shippingProtectionHasNonZeroPrice'] ? $this->setShippingProtectionPrice() : $this->setShippingProtectionPriceToZero();
    if (isset($conditions['shippingProtectionHasNonZeroBasePrice']))
      $conditions['shippingProtectionHasNonZeroBasePrice'] ? $this->setShippingProtectionBasePrice() : $this->setShippingProtectionBasePriceToZero();
  }

  private function setExtensionAttributesNull(): void
  {
    $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn(null);
  }

  private function setExtensionAttributes(): void
  {
    $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn($this->cartExtensionMock);
  }

  private function setShippingProtectionNull(): void
  {
    $this->cartExtensionMock->expects($this->any())->method('getShippingProtection')->willReturn(null);
  }

  private function setShippingProtection(): void
  {
    $this->cartExtensionMock->expects($this->any())->method('getShippingProtection')->willReturn($this->shippingProtectionMock);
  }
  private function setShippingProtectionPriceToZero(): void
  {
    $this->shippingProtectionMock->expects($this->any())->method('getPrice')->willReturn(0.0);
  }

  private function setShippingProtectionPrice(): void
  {
    $this->shippingProtectionMock->expects($this->any())->method('getPrice')->willReturn($this->shippingProtectionPrice);
  }

  private function setShippingProtectionBasePriceToZero(): void
  {
    $this->shippingProtectionMock->expects($this->any())->method('getBase')->willReturn(0.0);
  }

  private function setShippingProtectionBasePrice(): void
  {
    $this->shippingProtectionMock->expects($this->any())->method('getBase')->willReturn($this->shippingProtectionBasePrice);
  }
}
