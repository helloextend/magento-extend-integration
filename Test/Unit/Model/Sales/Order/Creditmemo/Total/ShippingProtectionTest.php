<?php

namespace Extend\Integration\Test\Unit\Model\Sales\Order\Creditmemo\Total;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Extend\Integration\Model\Sales\Order\Creditmemo\Total\ShippingProtection;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Model\ShippingProtectionFactory;
use Magento\Sales\Model\Order\Creditmemo;
use Extend\Integration\Api\Data\ShippingProtectionInterface;
use Magento\Sales\Api\Data\CreditmemoExtensionInterface;
use Extend\Integration\Model\ShippingProtection as BaseShippingProtectionModel;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentsCollection;
use Extend\Integration\Model\ShippingProtectionTotal;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;

class ShippingProtectionTest extends TestCase
{

  private const CREDITMEMO_ENTITY_TYPE_ID = 7;

  /**
   * @var ShippingProtection
   */
  private $model;

  /**
   * @var ShippingProtectionTotalRepositoryInterface|MockObject
   */
  private $shippingProtectionTotalRepositoryMock;

  /**
   * @var ShippingProtectionFactory|MockObject
   */
  private $shippingProtectionFactoryMock;

  /**
   * @var Creditmemo|MockObject
   */
  private $creditmemoMock;
  /**
   * @var Creditmemo|MockObject
   */
  private $preExistingCreditmemoMock;

  /**
   * @var ShippingProtectionInterface|MockObject
   */
  private $baseShippingProtectionModelMock;

  /**
   * @var CreditmemoExtensionInterface|MockObject
   */
  private $extensionAttributesMock;

  /**
   * @var Order|MockObject
   */
  private $orderMock;

  /**
   * @var CreditmemoCollection|MockObject
   */
  private $creditmemoCollectionMock;

  /**
   * @var ShipmentsCollection|MockObject
   */
  private $shipmentsCollectionMock;

  /**
   * @var ShippingProtectionTotal|MockObject
   */
  private $preexistingShippingProtectionTotal;

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
  private $creditmemoStartingGrandTotal;

  /**
   * @var float
   */
  private $creditmemoStartingBaseGrandTotal;

  /**
   * @var float
   */
  private $preExistingCreditmemoId;
  /**
   * @var float
   */
  private $creditmemoStartingTaxAmount;
  /**
   * @var float
   */
  private $preExistingCreditmemoShippingProtectionprice;
  /**
   * @var float
   */
  private $shippingProtectionTax;
  /**
   * @var float
   */
  private $preExistingCreditmemoShippingProtectionTax;

  protected function setUp(): void
  {
    // set primitive test values
    $this->shippingProtectionPrice = 123.45;
    $this->shippingProtectionBasePrice = 678.90;
    $this->shippingProtectionTax = 7.72;
    $this->creditmemoStartingGrandTotal = 1500.00;
    $this->creditmemoStartingBaseGrandTotal = 1500.00;
    $this->creditmemoStartingTaxAmount = 125.00;
    $this->preExistingCreditmemoId = 123;
    $this->preExistingCreditmemoShippingProtectionprice = 8.22;
    $this->preExistingCreditmemoShippingProtectionTax = 1.11;

    // create mock constructor args for the tested class
    $this->shippingProtectionTotalRepositoryMock = $this->createStub(ShippingProtectionTotalRepositoryInterface::class);
    $this->shippingProtectionFactoryMock = $this->createStub(ShippingProtectionFactory::class);
    $this->shippingProtectionFactoryMock
      ->method('create')
      ->willReturn(
        $this->createConfiguredMock(
          BaseShippingProtectionModel::class,
          [
            'setPrice' => null,
            'setBase' => null,
            'setCurrency' => null,
            'setBaseCurrency' => null,
            'setSpQuoteId' => null,
            'setShippingProtectionTax' => null,
            'getPrice' => 5.55,
            'getBase' => 6.66,
            'getCurrency' => 'USD',
            'getBaseCurrency' => 'USD',
            'getSpQuoteId' => '123456789',
            'getShippingProtectionTax' => 0.35
          ]
        )
      );
    // create the class to test
    $this->model = new ShippingProtection(
      $this->shippingProtectionTotalRepositoryMock,
      $this->shippingProtectionFactoryMock
    );

    // create argument for the tested method(s)
    $this->creditmemoMock = $this->getMockBuilder(Creditmemo::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getOrder', 'getExtensionAttributes', 'setGrandTotal', 'setBaseGrandTotal', 'getGrandTotal', 'getBaseGrandTotal', 'getTaxAmount', 'getBaseTaxAmount','setTaxAmount', 'setBaseTaxAmount', 'setExtensionAttributes', 'setData', 'isLast'])
      ->addMethods(['setShippingProtection', 'setBaseShippingProtection', 'getShippingProtection', 'getBaseShippingProtection', 'setShippingProtectionTax', 'getShippingProtectionTax', 'setOmitSp'])
      ->getMock();
    $this->extensionAttributesMock = $this->createStub(CreditmemoExtensionInterface::class);
    $this->creditmemoMock->method('getExtensionAttributes')
      ->willReturn($this->extensionAttributesMock);
    $this->creditmemoMock->method('getGrandTotal')->willReturn($this->creditmemoStartingGrandTotal);
    $this->creditmemoMock->method('getBaseGrandTotal')->willReturn($this->creditmemoStartingBaseGrandTotal);
    $this->creditmemoMock->method('getTaxAmount')->willReturn($this->creditmemoStartingTaxAmount);
    $this->creditmemoMock->method('getBaseTaxAmount')->willReturn($this->creditmemoStartingTaxAmount);

    // additional setup conditionally needed to cover the permutations in the test cases below
    $this->baseShippingProtectionModelMock = $this->createConfiguredMock(
      BaseShippingProtectionModel::class,
      [
        'getPrice' => $this->shippingProtectionPrice,
        'getBase' => $this->shippingProtectionBasePrice,
        'getShippingProtectionTax' => $this->shippingProtectionTax,
        'getCurrency' => 'USD',
        'getBaseCurrency' => 'USD',
        'getSpQuoteId' => '123456789'
        ]
      );
      $this->creditmemoCollectionMock = $this->createStub(CreditmemoCollection::class);
      $this->shipmentsCollectionMock = $this->createStub(ShipmentsCollection::class);
      $this->orderMock = $this->createConfiguredMock(
        Order::class,
        [
          'getCreditmemosCollection' => $this->creditmemoCollectionMock,
          ]
        );
    $this->preExistingCreditmemoMock = $this->createStub(Creditmemo::class);
    $this->preExistingCreditmemoMock->method('getId')->willReturn($this->preExistingCreditmemoId);
    $this->preexistingShippingProtectionTotal = $this->createStub(ShippingProtectionTotal::class);
  }

  /* =================================================================================================== */
  /* ============================================== tests ============================================== */
  /* =================================================================================================== */

  public function testCollectIfNoShippingProtection()
  {
    // shipping protection does not exist
    $this->setTestConditions([
      'extensionAttributesHasShippingProtection' => false
    ]);

    // expect no call to getOrder
    $this->creditmemoMock->expects($this->never())->method('getOrder');

    // run the tested function
    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectIfShippingProtectionAndNoExistingCreditMemosAndNoShipmentsForOrder()
  {
    // order with shipping protection has no pre-existing credit memos or shipments
    $this->setTestConditions([
      'extensionAttributesHasShippingProtection' => true,
      'existingCreditMemo' => false,
      'existingShipment' => false
    ]);

    // expect the setters and corresponding getters to be called twice each with corresponding price values
    $this->expectShippingProtectionPriceGettersAndSettersToBeCalledOnceEachWithCorrespondingPriceValues();

    // run the tested function
    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectIfShippingProtectionAndOneExistingCreditMemoNotRefundingShippingProtectionWithNoShipments()
  {
    // order with shipping protection has one pre-existing credit memo and no shipments
    $this->setTestConditions([
      'extensionAttributesHasShippingProtection' => true,
      'existingCreditMemo' => true,
      'existingShipment' => false
    ]);

    // expect the setters and corresponding getters to be called twice each with corresponding price values
    $this->expectShippingProtectionPriceGettersAndSettersToBeCalledOnceEachWithCorrespondingPriceValues();

    // run the tested function
    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectIfShippingProtectionAndOneExistingCreditMemoRefundingShippingProtectionWithNoShipments()
  {
    // order with shipping protection has one pre-existing credit memo that refunds shipping protection and no shipments
    $this->setTestConditions([
      'extensionAttributesHasShippingProtection' => true,
      'existingCreditMemo' => true,
      'existingCreditMemoRefundsShippingProtection' => true,
      'existingShipment' => false
    ]);

    // expect the credit memo to be zeroed out
    $this->expectOmitSpSetToTrue();

    // run the tested function
    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectIfShippingProtectionAndNoExistingCreditmemoWithExistingShipment()
  {
    // order with shipping protection has no pre-existing credit memo and one shipment
    $this->setTestConditions([
      'extensionAttributesHasShippingProtection' => true,
      'existingCreditMemo' => false,
      'existingShipment' => true
    ]);

    // expect the credit memo to be zeroed out
    $this->expectOmitSpSetToTrue();

    // run the tested function
    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectExistingCreditmemoWithSpgSp()
  {
    // order with shipping protection has one pre-existing credit memo that refunds shipping protection
    $this->setTestConditions([
      'extensionAttributesHasShippingProtection' => true,
      'existingCreditMemo' => true,
    ]);
    $this->creditmemoCollectionMock->method('getItems')->willReturn([$this->preExistingCreditmemoMock]);
    $this->shippingProtectionTotalRepositoryMock
      ->method('get')
      ->willReturn($this->preexistingShippingProtectionTotal);
    $this->preexistingShippingProtectionTotal->method('getData')->willReturn(true);
    $this->preexistingShippingProtectionTotal->method('getShippingProtectionPrice')->willReturn(0.0);
    $this->preexistingShippingProtectionTotal->method('getOfferType')->willReturn(ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE);

    // expect the credit memo to be zeroed out
    $this->expectOmitSpSetToTrue();

    // run the tested function
    $this->model->collect($this->creditmemoMock);
  }

  /* =================================================================================================== */
  /* ========================== helper methods for setting up test conditions ========================== */
  /* =================================================================================================== */

  /**
   * @param array $conditions
   * 1. extensionAttributesHasShippingProtection
   * 2. existingCreditMemo
   * 3. existingCreditMemoRefundsShippingProtection
   * 4. existingShipment
   */

  private function setTestConditions(
    array $conditions
  ) {
    $this->setShippingProtectionToExtensionAttributes($conditions['extensionAttributesHasShippingProtection'] ?? false);
    $this->setExistingCreditMemo($conditions['existingCreditMemo'] ?? false);
    $this->setExistingCreditMemoRefundsShippingProtection($conditions['existingCreditMemoRefundsShippingProtection'] ?? false);
    $this->setExistingShipment($conditions['existingShipment'] ?? false);
  }

  private function setShippingProtectionToExtensionAttributes(bool $shippingProtectionExists)
  {
    if (isset($shippingProtectionExists) && $shippingProtectionExists) {
      $this->extensionAttributesMock->method('getShippingProtection')->willReturn($this->baseShippingProtectionModelMock);
      $this->creditmemoMock->method('getOrder')->willReturn($this->orderMock);
    } else {
      $this->extensionAttributesMock->method('getShippingProtection')->willReturn(null);
    }
  }

  private function setExistingCreditMemo(bool $existingCreditMemo)
  {
    if (isset($existingCreditMemo) && $existingCreditMemo) {
      $this->creditmemoCollectionMock->method('getItems')->willReturn([$this->preExistingCreditmemoMock]);
    } else {
      $this->creditmemoCollectionMock->method('getItems')->willReturn([]);
    }
  }

  private function setExistingCreditMemoRefundsShippingProtection(bool $existingCreditMemoRefundsShippingProtection)
  {
    if ($existingCreditMemoRefundsShippingProtection) {
      $this->shippingProtectionTotalRepositoryMock
        ->method('get')
        ->with($this->preExistingCreditmemoId, $this::CREDITMEMO_ENTITY_TYPE_ID)
        ->willReturn($this->preexistingShippingProtectionTotal);
      $this->preexistingShippingProtectionTotal->method('getData')->willReturn(true);
      $this->preexistingShippingProtectionTotal->method('getShippingProtectionPrice')->willReturn($this->preExistingCreditmemoShippingProtectionprice);
    }
  }

  private function setExistingShipment(bool $existingShipment)
  {
    if ($existingShipment) {
      $this->orderMock->method('getShipmentsCollection')->willReturn(['there is a shipment']);
    } else {
      $this->orderMock->method('getShipmentsCollection')->willReturn([]);
    }
  }

  /* =================================================================================================== */
  /* ============================== helper methods for validating results ============================== */
  /* =================================================================================================== */

  /**
   *  This method asserts that when SP is included in the credit memo, the getters and setters are called twice each
   *  with the corresponding price values. The initial call will be to reset all of the totals and tax values to not include
   *  the sp tax. The second call will be to add the sp tax back into the totals and tax values.
   * */
  private function expectShippingProtectionPriceGettersAndSettersToBeCalledOnceEachWithCorrespondingPriceValues()
  {
    $this->baseShippingProtectionModelMock->expects($this->once())->method('getShippingProtectionTax')->willReturn($this->shippingProtectionTax);
    $this->creditmemoMock->expects($this->once())->method('setShippingProtection')->with($this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())->method('setBaseShippingProtection')->with($this->shippingProtectionBasePrice);
    $this->creditmemoMock->expects($this->once())->method('setShippingProtectionTax')->with($this->shippingProtectionTax);
    $this->creditmemoMock->expects($this->once())->method('getGrandTotal')->willReturn($this->creditmemoStartingGrandTotal);
    $this->creditmemoMock->expects($this->once())->method('setGrandTotal')->with($this->creditmemoStartingGrandTotal + $this->shippingProtectionPrice + $this->shippingProtectionTax);
    $this->creditmemoMock->expects($this->once())->method('getBaseGrandTotal')->willReturn($this->creditmemoStartingBaseGrandTotal);
    $this->creditmemoMock->expects($this->once())->method('setBaseGrandTotal')->with($this->creditmemoStartingBaseGrandTotal + $this->shippingProtectionBasePrice + $this->shippingProtectionTax);
    $this->creditmemoMock->expects($this->once())->method('getTaxAmount')->willReturn($this->creditmemoStartingTaxAmount);
    $this->creditmemoMock->expects($this->once())->method('setTaxAmount')->with($this->creditmemoStartingTaxAmount + $this->shippingProtectionTax);
    $this->creditmemoMock->expects($this->once())->method('getBaseTaxAmount')->willReturn($this->creditmemoStartingTaxAmount);
    $this->creditmemoMock->expects($this->once())->method('setBaseTaxAmount')->with($this->creditmemoStartingTaxAmount + $this->shippingProtectionTax);
  }

  private function expectGrandTotalToBeSetToSumOfStartingGrandTotalAndShippingProtectionPrice()
  {
    $this->creditmemoMock->expects($this->once())->method('setGrandTotal')->with(
      $this->creditmemoStartingGrandTotal + $this->shippingProtectionPrice + $this->shippingProtectionTax
    );
  }

  private function expectBaseGrandTotalToBeSetToSumOfStartingBaseGrandTotalAndShippingProtectionBasePrice()
  {
    $this->creditmemoMock->expects($this->once())->method('setBaseGrandTotal')->with(
      $this->creditmemoStartingBaseGrandTotal + $this->shippingProtectionBasePrice
    );
  }

  private function expectOmitSpSetToTrue()
  {
    $this->creditmemoMock->expects($this->exactly(2))->method('setOmitSp')->withConsecutive([false], [true]);
  }
}
