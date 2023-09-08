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
    // create mock constructor args for the tested class
    $this->shippingProtectionTotalRepositoryMock = $this->getMockBuilder(ShippingProtectionTotalRepositoryInterface::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMockForAbstractClass();
    $this->shippingProtectionFactoryMock = $this->getMockBuilder(ShippingProtectionFactory::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['create'])
      ->getMock();
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
    // create arguments for the tested method(s)
    $this->baseShippingProtectionModelMock = $this->getMockBuilder(BaseShippingProtectionModel::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getPrice', 'getBase', 'getCurrency', 'getBaseCurrency', 'getSpQuoteId'])
      ->getMock();
    $this->extensionAttributesMock = $this->getMockBuilder(CreditmemoExtensionInterface::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getShippingProtection', 'setShippingProtection'])
      ->getMock();
    $this->creditmemoMock = $this->getMockBuilder(Creditmemo::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getOrder', 'getExtensionAttributes', 'setGrandTotal', 'setBaseGrandTotal', 'getGrandTotal', 'getBaseGrandTotal', 'setExtensionAttributes', 'setData'])
      ->addMethods(['setShippingProtection', 'setBaseShippingProtection', 'getShippingProtection', 'getBaseShippingProtection'])
      ->getMock();
    $this->creditmemoMock->method('getExtensionAttributes')
      ->willReturn($this->extensionAttributesMock);

    $this->orderMock = $this->getMockBuilder(Order::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getCreditmemosCollection', 'getShipmentsCollection'])
      ->getMock();

    $this->creditmemoCollectionMock = $this->getMockBuilder(CreditmemoCollection::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getItems'])
      ->getMock();

    $this->shipmentsCollectionMock = $this->getMockBuilder(ShipmentsCollection::class)
      ->disableOriginalConstructor()
      ->getMock();


    $this->orderMock->method('getCreditmemosCollection')
      ->willReturn($this->creditmemoCollectionMock);

    $this->shippingProtectionPrice = 123.45;
    $this->shippingProtectionBasePrice = 678.90;
    $this->creditmemoStartingGrandTotal = 1000.00;
    $this->creditmemoStartingBaseGrandTotal = 2000.00;
    $this->preExistingCreditmemoId = 123;
    $this->preExistingCreditmemoShippingProtectionprice = 8.22;

    $this->creditmemoMock->method('getGrandTotal')
      ->willReturn($this->creditmemoStartingGrandTotal);
    $this->creditmemoMock->method('getBaseGrandTotal')
      ->willReturn($this->creditmemoStartingBaseGrandTotal);

    $this->preExistingCreditmemoMock = $this->getMockBuilder(Creditmemo::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getId'])
      ->getMock();
    $this->preExistingCreditmemoMock
      ->method('getId')
      ->willReturn($this->preExistingCreditmemoId);

    $this->preexistingShippingProtectionTotal = $this->getMockBuilder(ShippingProtectionTotal::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getData', 'getShippingProtectionPrice', 'getSpQuoteId'])
      ->addMethods(['getCurrency', 'getBaseCurrency'])
      ->getMock();
  }

  public function testCollectIfNoShippingProtection()
  {
    // shipping protection does not exist
    $this->extensionAttributesMock->method('getShippingProtection')
      ->willReturn(null);
    // expect no call to getOrder
    $this->creditmemoMock->expects($this->never())
      ->method('getOrder');

    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectIfShippingProtectionAndNoExistingCreditMemosAndNoShipmentsForOrder()
  {
    // order with shipping protection has no pre-existing credit memos or shipments
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getPrice')
      ->willReturn($this->shippingProtectionPrice);
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getBase')
      ->willReturn($this->shippingProtectionBasePrice);
    $this->extensionAttributesMock->method('getShippingProtection')
      ->willReturn($this->baseShippingProtectionModelMock);
    $this->creditmemoMock->expects($this->any())
      ->method('getOrder')
      ->willReturn($this->orderMock);
    $this->creditmemoCollectionMock->expects($this->once())
      ->method('getItems')
      ->willReturn([]);
    $this->orderMock->expects($this->once())
      ->method('getShipmentsCollection')
      ->willReturn([]);

    // expect the setters and corresponding getters to be called once each
    $this->creditmemoMock->expects($this->once())
      ->method('setShippingProtection')
      ->with($this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())
      ->method('setBaseShippingProtection')
      ->with($this->shippingProtectionBasePrice);
    $this->creditmemoMock->expects($this->once())
      ->method('getShippingProtection')
      ->willReturn($this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())
      ->method('getBaseShippingProtection')
      ->willReturn($this->shippingProtectionBasePrice);
    // expect the new grand total written to the creditmemo to be starting grand total + shipping protection price
    $this->creditmemoMock->expects($this->once())
      ->method('setGrandTotal')
      ->with($this->creditmemoStartingGrandTotal + $this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())
      ->method('setBaseGrandTotal')
      ->with($this->creditmemoStartingBaseGrandTotal + $this->shippingProtectionBasePrice);

    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectIfShippingProtectionAndOneExistingCreditMemoNotRefundingShippingProtectionWithNoShipments()
  {
    // order with shipping protection has one pre-existing credit memo and no shipments
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getPrice')
      ->willReturn($this->shippingProtectionPrice);
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getBase')
      ->willReturn($this->shippingProtectionBasePrice);
    $this->extensionAttributesMock->method('getShippingProtection')
      ->willReturn($this->baseShippingProtectionModelMock);
    $this->creditmemoMock->expects($this->any())
      ->method('getOrder')
      ->willReturn($this->orderMock);
    $this->creditmemoCollectionMock->expects($this->once())
      ->method('getItems')
      ->willReturn([
        $this->preExistingCreditmemoMock
      ]);
    $this->shippingProtectionTotalRepositoryMock->expects($this->once())
      ->method('get')
      ->with($this->preExistingCreditmemoId, $this::CREDITMEMO_ENTITY_TYPE_ID)
      ->willReturn($this->preexistingShippingProtectionTotal);
    $this->preexistingShippingProtectionTotal->expects($this->once())
      ->method('getData')
      ->willReturn(null);
    $this->orderMock->expects($this->once())
      ->method('getShipmentsCollection')
      ->willReturn([]);

    // expect the setters and corresponding getters to be called once each
    $this->creditmemoMock->expects($this->once())
      ->method('setShippingProtection')
      ->with($this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())
      ->method('setBaseShippingProtection')
      ->with($this->shippingProtectionBasePrice);
    $this->creditmemoMock->expects($this->once())
      ->method('getShippingProtection')
      ->willReturn($this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())
      ->method('getBaseShippingProtection')
      ->willReturn($this->shippingProtectionBasePrice);
    // expect the new grand total written to the creditmemo to be starting grand total + shipping protection price
    $this->creditmemoMock->expects($this->once())
      ->method('setGrandTotal')
      ->with($this->creditmemoStartingGrandTotal + $this->shippingProtectionPrice);
    $this->creditmemoMock->expects($this->once())
      ->method('setBaseGrandTotal')
      ->with($this->creditmemoStartingBaseGrandTotal + $this->shippingProtectionBasePrice);

    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectIfShippingProtectionAndOneExistingCreditMemoRefundingShippingProtectionWithNoShipments()
  {
    // order with shipping protection has one pre-existing credit memo and no shipments
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getPrice')
      ->willReturn($this->shippingProtectionPrice);
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getCurrency')
      ->willReturn('USD');
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getBaseCurrency')
      ->willReturn('USD');
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getSpQuoteId')
      ->willReturn('123456789');
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getBase')
      ->willReturn($this->shippingProtectionBasePrice);
    $this->extensionAttributesMock->method('getShippingProtection')
      ->willReturn($this->baseShippingProtectionModelMock);
    $this->creditmemoMock->expects($this->any())
      ->method('setExtensionAttributes')
      ->willReturn(null);
    $this->creditmemoMock->expects($this->any())
      ->method('getOrder')
      ->willReturn($this->orderMock);
    $this->creditmemoCollectionMock->expects($this->once())
      ->method('getItems')
      ->willReturn([
        $this->preExistingCreditmemoMock
      ]);
    $this->shippingProtectionTotalRepositoryMock->expects($this->once())
      ->method('get')
      ->with($this->preExistingCreditmemoId, $this::CREDITMEMO_ENTITY_TYPE_ID)
      ->willReturn($this->preexistingShippingProtectionTotal);
    $this->preexistingShippingProtectionTotal->expects($this->once())
      ->method('getData')
      ->willReturn(true);
    $this->preexistingShippingProtectionTotal->expects($this->once())
      ->method('getShippingProtectionPrice')
      ->willReturn($this->preExistingCreditmemoShippingProtectionprice);
    $this->orderMock->expects($this->once())
      ->method('getShipmentsCollection')
      ->willReturn([]);

    // expect the credit memo to be zeroed out
    $this->creditmemoMock->expects($this->once())
      ->method('setShippingProtection')
      ->with(0.0);
    $this->creditmemoMock->expects($this->once())
      ->method('setBaseShippingProtection')
      ->with(0.0);
    $this->creditmemoMock->expects($this->once())
      ->method('setExtensionAttributes')
      ->with($this->anything());
    $this->creditmemoMock->expects($this->once())
      ->method('setData')
      ->with('original_shipping_protection', 0);

    $this->model->collect($this->creditmemoMock);
  }

  public function testCollectIfShippingProtectionAndNoExistingCreditmemoWithExistingShipment()
  {
    // order with shipping protection has no pre-existing credit memo and one shipment
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getPrice')
      ->willReturn($this->shippingProtectionPrice);
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getCurrency')
      ->willReturn('USD');
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getBaseCurrency')
      ->willReturn('USD');
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getSpQuoteId')
      ->willReturn('123456789');
    $this->baseShippingProtectionModelMock->expects($this->any())
      ->method('getBase')
      ->willReturn($this->shippingProtectionBasePrice);
    $this->extensionAttributesMock->method('getShippingProtection')
      ->willReturn($this->baseShippingProtectionModelMock);
    $this->creditmemoMock->expects($this->any())
      ->method('setExtensionAttributes')
      ->willReturn(null);
    $this->creditmemoMock->expects($this->any())
      ->method('getOrder')
      ->willReturn($this->orderMock);
    $this->creditmemoCollectionMock->expects($this->once())
      ->method('getItems')
      ->willReturn([]);
    $this->orderMock->expects($this->once())
      ->method('getShipmentsCollection')
      ->willReturn([
        'there is a shipment'
      ]);

    // expect the credit memo to be zeroed out
    $this->creditmemoMock->expects($this->once())
      ->method('setShippingProtection')
      ->with(0.0);
    $this->creditmemoMock->expects($this->once())
      ->method('setBaseShippingProtection')
      ->with(0.0);
    $this->creditmemoMock->expects($this->once())
      ->method('setExtensionAttributes')
      ->with($this->anything());
    $this->creditmemoMock->expects($this->once())
      ->method('setData')
      ->with('original_shipping_protection', 0);

    $this->model->collect($this->creditmemoMock);
  }
}
