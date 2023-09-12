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
  private $preExistingCreditmemoShippingProtectionprice;

  protected function setUp(): void
  {
    // set primitive test values
    $this->shippingProtectionPrice = 123.45;
    $this->shippingProtectionBasePrice = 678.90;
    $this->creditmemoStartingGrandTotal = 1000.00;
    $this->creditmemoStartingBaseGrandTotal = 2000.00;
    $this->preExistingCreditmemoId = 123;
    $this->preExistingCreditmemoShippingProtectionprice = 8.22;

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
            'getPrice' => 5.55,
            'getBase' => 6.66,
            'getCurrency' => 'USD',
            'getBaseCurrency' => 'USD',
            'getSpQuoteId' => '123456789'
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
      ->onlyMethods(['getOrder', 'getExtensionAttributes', 'setGrandTotal', 'setBaseGrandTotal', 'getGrandTotal', 'getBaseGrandTotal', 'setExtensionAttributes', 'setData'])
      ->addMethods(['setShippingProtection', 'setBaseShippingProtection', 'getShippingProtection', 'getBaseShippingProtection'])
      ->getMock();
    $this->extensionAttributesMock = $this->createStub(CreditmemoExtensionInterface::class);
    $this->creditmemoMock->method('getExtensionAttributes')
      ->willReturn($this->extensionAttributesMock);
    $this->creditmemoMock->method('getGrandTotal')->willReturn($this->creditmemoStartingGrandTotal);
    $this->creditmemoMock->method('getBaseGrandTotal')->willReturn($this->creditmemoStartingBaseGrandTotal);

    // additional setup conditionally needed to cover the permutations in the test cases below
    $this->baseShippingProtectionModelMock = $this->createConfiguredMock(
      BaseShippingProtectionModel::class,
      [
        'getPrice' => $this->shippingProtectionPrice,
        'getBase' => $this->shippingProtectionBasePrice,
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

    // expect the setters and corresponding getters to be called once each
    $this->expectShippingProtectionPriceGettersAndSettersToBeCalledOnceEachWithCorrespondingPriceValues();

    // expect the new grand total written to the creditmemo to be starting grand total + shipping protection price
    $this->expectGrandTotalToBeSetToSumOfStartingGrandTotalAndShippingProtectionPrice();
    $this->expectBaseGrandTotalToBeSetToSumOfStartingBaseGrandTotalAndShippingProtectionBasePrice();

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

    // expect the setters and corresponding getters to be called once each
    $this->expectShippingProtectionPriceGettersAndSettersToBeCalledOnceEachWithCorrespondingPriceValues();

    // expect the new grand total written to the creditmemo to be starting grand total + shipping protection price
    $this->expectGrandTotalToBeSetToSumOfStartingGrandTotalAndShippingProtectionPrice();
    $this->expectBaseGrandTotalToBeSetToSumOfStartingBaseGrandTotalAndShippingProtectionBasePrice();

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
    $this->expectCreditMemoToBeZeroedOut();

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
    $this->expectCreditMemoToBeZeroedOut();

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

  private function expectShippingProtectionPriceGettersAndSettersToBeCalledOnceEachWithCorrespondingPriceValues()
  {
    $this->creditmemoMock->expects($this->once())->method('setShippingProtection')->with($this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())->method('setBaseShippingProtection')->with($this->shippingProtectionBasePrice);
    $this->creditmemoMock->expects($this->once())->method('getShippingProtection')->willReturn($this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())->method('getBaseShippingProtection')->willReturn($this->shippingProtectionBasePrice);
  }

  private function expectGrandTotalToBeSetToSumOfStartingGrandTotalAndShippingProtectionPrice()
  {
    $this->creditmemoMock->expects($this->once())->method('setGrandTotal')->with(
      $this->creditmemoStartingGrandTotal + $this->shippingProtectionPrice
    );
  }

  private function expectBaseGrandTotalToBeSetToSumOfStartingBaseGrandTotalAndShippingProtectionBasePrice()
  {
    $this->creditmemoMock->expects($this->once())->method('setBaseGrandTotal')->with(
      $this->creditmemoStartingBaseGrandTotal + $this->shippingProtectionBasePrice
    );
  }

  private function expectCreditMemoToBeZeroedOut()
  {
    $this->creditmemoMock->expects($this->once())->method('setShippingProtection')->with(0.0);
    $this->creditmemoMock->expects($this->once())->method('setBaseShippingProtection')->with(0.0);
    $this->creditmemoMock->expects($this->once())->method('setExtensionAttributes')->with($this->anything());
    $this->creditmemoMock->expects($this->once())->method('setData')->with('original_shipping_protection', 0);
  }
}
