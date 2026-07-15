<?php

namespace Extend\Integration\Test\Unit\Model\Sales\Order\Total;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Extend\Integration\Model\Sales\Order\Total\ShippingProtection;
use Magento\Sales\Model\Order;
use Extend\Integration\Model\ShippingProtection as BaseShippingProtectionModel;
use Magento\Sales\Api\Data\OrderExtensionInterface;

class ShippingProtectionTest extends TestCase
{

  /**
   * @var float
   */
    private $grandTotal;

  /**
   * @var float
   */
    private $baseGrandTotal;

  /**
   * @var float
   */
    private $shippingProtectionBase;

  /**
   * @var float
   */
    private $shippingProtectionPrice;

  /**
   * @var ShippingProtection
   */
    private $testSubject;

  /**
   * @var Order | MockObject
   */
    private $orderMock;

  /**
   * @var BaseShippingProtectionModel | MockObject
   */
    private $baseShippingProtectionModelMock;

  /**
   * @var OrderExtensionInterface | MockObject
   */
    private $extensionAttributesMock;

  /**
   * @var array
   */
    private $magicGetData;

  /**
   * @var array
   */
    private $setDataCalls;

    protected function setUp(): void
    {
      // set primitive test values
        $this->grandTotal = 123.45;
        $this->baseGrandTotal = 234.56;
        $this->shippingProtectionBase = 34.56;
        $this->shippingProtectionPrice = 45.67;

      // create mock constructor args for the tested class
      // none needed

      // create the class to test
        $this->testSubject = new ShippingProtection();

      // create arguments for tested method(s)
        $this->orderMock = $this->getMockBuilder(Order::class)
        ->disableOriginalConstructor()
        ->onlyMethods([
        'setGrandTotal',
        'setBaseGrandTotal',
        'getExtensionAttributes',
        'getGrandTotal',
        'getBaseGrandTotal',
        'getData',
        'setData'
        ])
        ->getMock();
        $this->magicGetData = ['order' => $this->orderMock];
        $this->setDataCalls = [];
        $this->orderMock->method('getData')->willReturnCallback(function ($key) {
            return $this->magicGetData[$key] ?? null;
        });
        $this->orderMock->method('setData')->willReturnCallback(function ($key, $value = null) {
            $this->setDataCalls[$key] = $value;
            return $this->orderMock;
        });
        $this->orderMock->method('getGrandTotal')->willReturn($this->grandTotal);
        $this->orderMock->method('getBaseGrandTotal')->willReturn($this->baseGrandTotal);

      // additional setup needed to cover the permutations in the test cases below
        $this->extensionAttributesMock = $this->createStub(OrderExtensionInterface::class);
        $this->orderMock->method('getExtensionAttributes')->willReturn($this->extensionAttributesMock);
        $this->baseShippingProtectionModelMock = $this->createStub(BaseShippingProtectionModel::class);
        $this->baseShippingProtectionModelMock->method('getBase')->willReturn($this->shippingProtectionBase);
        $this->baseShippingProtectionModelMock->method('getPrice')->willReturn($this->shippingProtectionPrice);
    }

    public function testExtensionAttributesHasNoShippingProtection()
    {
      // set up test conditions
        $this->setTestConditions([
        'extensionAttributesHasShippingProtection' => false,
        ]);
      // set expectations
        $this->expectNothingToHappen();
      // run the test
        $this->testSubject->collect($this->orderMock);
        $this->assertNothingHappened();
    }

    public function testExtensionAttributesHasShippingProtection()
    {
      // set up test conditions
        $this->setTestConditions([
        'extensionAttributesHasShippingProtection' => true,
        ]);
        $this->magicGetData['shipping_protection'] = $this->shippingProtectionPrice;
        $this->magicGetData['base_shipping_protection'] = $this->shippingProtectionBase;
      // set expectations
        $this->expectOrderValuesToBeUpdated();
      // run the test
        $this->testSubject->collect($this->orderMock);
        $this->assertOrderValuesWereUpdated();
    }

  /* =================================================================================================== */
  /* ========================== helper methods for setting up test conditions ========================== */
  /* =================================================================================================== */

  /**
   * @param array $conditions
   * 1. extensionAttributesHaveShippingProtection
   */

    private function setTestConditions(
        array $conditions
    ) {
        $this->setExtensionAttributesHasShippingProtection($conditions['extensionAttributesHasShippingProtection'] ?? false);
    }

    private function setExtensionAttributesHasShippingProtection(bool $condition)
    {
        if (isset($condition) && $condition) {
            $this->extensionAttributesMock->method('getShippingProtection')->willReturn($this->baseShippingProtectionModelMock);
        } else {
            $this->extensionAttributesMock->method('getShippingProtection')->willReturn(null);
        }
    }

  /* =================================================================================================== */
  /* ============================== helper methods for validating results ============================== */
  /* =================================================================================================== */

    private function expectNothingToHappen()
    {
        $this->orderMock->expects($this->never())->method('setGrandTotal');
        $this->orderMock->expects($this->never())->method('setBaseGrandTotal');
    }

    private function assertNothingHappened()
    {
        $this->assertSame([], $this->setDataCalls);
    }

    private function expectOrderValuesToBeUpdated()
    {
        $this->orderMock->expects($this->once())->method('setGrandTotal')->with($this->grandTotal + $this->shippingProtectionPrice);
        $this->orderMock->expects($this->once())->method('setBaseGrandTotal')->with($this->baseGrandTotal + $this->shippingProtectionBase);
    }

    private function assertOrderValuesWereUpdated()
    {
        $this->assertArrayHasKey('base_shipping_protection', $this->setDataCalls);
        $this->assertSame($this->shippingProtectionBase, $this->setDataCalls['base_shipping_protection']);
        $this->assertArrayHasKey('shipping_protection', $this->setDataCalls);
        $this->assertSame($this->shippingProtectionPrice, $this->setDataCalls['shipping_protection']);
    }
}
