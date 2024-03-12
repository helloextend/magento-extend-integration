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
use Magento\Quote\Api\Data\CartExtension;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Calculation;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Extend\Integration\Model\Quote\Total\ShippingProtection;
use Extend\Integration\Model\ShippingProtectionTotal;

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
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Store
     */
    private $storeMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var ScopeInterface|MockObject
     */
    private $scopeMock;

    /**
     * @var Calculation|MockObject
     */
    private $calculationMock;

    /**
     * @var RateRequest|MockObject
     */
    private $rateRequestMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var ShippingAssignmentInterface|MockObject
     */
    private $shippingAssignmentMock;

    /**
     * @var Total
     */
    private $total;

    /**
     * @var ShippingProtection|MockObject
     */
    private $shippingProtectionMock;

    /**
     * @var ShippingProtectionTotal|MockObject
     */
    private $shippingProtectionTotalMock;

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
    /**
     * @var array|array[]
     */
    private array $items;

    /**
     * @var float
     */
    private $spTaxClassId;

    protected function setUp(): void
    {
        // class under test's constructor dependencies
        $this->shippingProtectionTotalRepositoryMock = $this->createMock(ShippingProtectionTotalRepositoryInterface::class);
        $this->storeManagerMock = $this->createStub(StoreManagerInterface::class);
        $this->scopeConfigMock = $this->createStub(ScopeConfigInterface::class);
        $this->scopeMock = $this->createStub(ScopeInterface::class);
        $this->serializerMock = $this->createStub(SerializerInterface::class);
        $this->cartExtensionMock = $this->createStub(CartExtension::class);
        $this->storeMock = $this->createStub(Store::class);
        $this->storeManagerMock->method('getStore')->willReturn($this->storeMock);
        $this->calculationMock = $this->createStub(Calculation::class);
        $this->rateRequestMock = $this->createStub(RateRequest::class);
        $this->calculationMock->method('getRateRequest')->willReturn($this->rateRequestMock);
        $this->cartExtensionFactoryMock = $this->createConfiguredMock(
            CartExtensionFactory::class,
            ['create' => $this->cartExtensionMock]
        );
        
        $this->spTaxClassId = 2;
        $this->shippingProtectionTotalMock = $this->createStub(\Extend\Integration\Model\ShippingProtectionTotal::class);
        
        $this->shippingProtectionTotalMock->method('getData')
            ->willReturnMap([
                ['extend_shipping_protection_id', 72],
                ['entity_id', 72],
                ['sp_quote_id', "10f2df29-d04c-4fb2-a857-6d382251e596"],
                ['shipping_protection_base_price', null, 0.98],
                ['shipping_protection_price', null, 0.98],
                ['shipping_protection_tax', null, 0.0],
                ['shipping_protection_base_currency', null, 'USD'],
                ['shipping_protection_currency', null, 'USD'],
                ['entity_type_id', null, 4],
            ]);

        $this->testSubject = new ShippingProtection(
            $this->shippingProtectionTotalRepositoryMock,
            $this->storeManagerMock,
            $this->scopeConfigMock,
            $this->serializerMock,
            $this->cartExtensionFactoryMock,
            $this->calculationMock
        );
        
        // additional test dependencies
        $this->shippingAssignmentMock = $this->createConfiguredMock(ShippingAssignmentInterface::class, [
            'getShipping' => $this->createConfiguredMock(ShippingInterface::class, [
                'getAddress' => $this->createStub(Address::class)
                ])
            ]);
        $this->total = new Total();
        $this->shippingProtectionMock = $this->createStub(BaseShippingProtectionModel::class);
        $this->quoteMock = $this->createStub(Quote::class);
        $this->testSubject->setCode('shipping_protection');
        
        $this->items = [
            0 => [
                'id' => 1,
                'name' => 'test 1',
                'qty' => 1,
                'price' => 10.00,
                'base_price' => 10.00
            ],
            1 => [
                'id' => 2,
                'name' => 'test 2',
                'qty' => 1,
                'price' => 10.00,
                'base_price' => 10.00
            ],
            2 => [
                'id' => 3,
                'name' => 'test 3',
                'qty' => 1,
                'price' => 10.00,
                'base_price' => 10.00
            ]
        ];
    }

    public function testCollectWhenItemsAreNull()
    {
        $this->shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([]);

        // test and assert
        $this->runCollect();
        $this->assertEquals(0, $this->total->getBaseTotalAmount('shipping_protection'));
    }

    public function testCollectWhenExtensionAttributesAreNull()
    {
        $this->shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([$this->items]);

        // set up
        $this->setTestConditions([
            'extensionAttributesExist' => false
        ]);
        $this->cartExtensionFactoryMock->expects($this->once())->method('create');
        // test and assert
        $this->runCollect();
        $this->assertEquals(0, $this->total->getBaseTotalAmount('shipping_protection'));
    }

    public function testCollectWhenShippingProtectionIsNull()
    {
        $this->shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([$this->items]);

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
        $this->shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([$this->items]);

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
        $this->shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([$this->items]);

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
        $this->shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([$this->items]);

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

    public function testCollectWhenShippingProtectionTaxClassIsNotZero()
    {
        $this->shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([$this->items]);
        $this->scopeConfigMock->method('getValue')
        ->with('extend_plans/shipping_protection/shipping_protection_tax_class', ScopeInterface::SCOPE_STORE)
        ->willReturn(2);
        $this->shippingProtectionTotalRepositoryMock->expects($this->once())
            ->method('get')
            ->with(72, 4)
            ->willReturn($this->shippingProtectionTotalMock);

        // set up
        $this->setTestConditions([
            'extensionAttributesExist' => true,
            'shippingProtectionExists' => true,
            'shippingProtectionHasNonZeroPrice' => true,
            'shippingProtectionHasNonZeroBasePrice' => true
        ]);
        $this->calculationMock->expects($this->once())->method('getRate')->willReturn(10);
        // test and assert
        $this->runCollect();
        $this->assertEquals($this->shippingProtectionBasePrice, $this->total->getBaseTotalAmount('shipping_protection'));
        $this->assertEquals($this->shippingProtectionPrice, $this->total->getTotalAmount('shipping_protection'));
        $this->assertEquals(54.321, $this->total->getBaseTotalAmount('tax'));
        $this->assertEquals(54.321, $this->total->getTotalAmount('tax'));
    }

    public function testCollectWhenShippingProtectionTaxClassIsZero()
    {
        $this->shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([$this->items]);
        $this->scopeConfigMock->method('getValue')
        ->with('extend_plans/shipping_protection/shipping_protection_tax_class', ScopeInterface::SCOPE_STORE)
        ->willReturn(0);

        // set up
        $this->setTestConditions([
            'extensionAttributesExist' => true,
            'shippingProtectionExists' => true,
            'shippingProtectionHasNonZeroPrice' => true,
            'shippingProtectionHasNonZeroBasePrice' => true
        ]);
        $this->calculationMock->expects($this->once())->method('getRate')->willReturn(10);
        // test and assert
        $this->runCollect();
        $this->assertEquals($this->shippingProtectionBasePrice, $this->total->getBaseTotalAmount('shipping_protection'));
        $this->assertEquals($this->shippingProtectionPrice, $this->total->getTotalAmount('shipping_protection'));
        $this->assertEquals(0, $this->total->getBaseTotalAmount('tax'));
        $this->assertEquals(0, $this->total->getTotalAmount('tax'));
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
        (isset($conditions['extensionAttributesExist']) && $conditions['extensionAttributesExist']) ?
            $this->setExtensionAttributes() : $this->setExtensionAttributesNull();

        (isset($conditions['shippingProtectionHasNonZeroPrice']) && $conditions['shippingProtectionHasNonZeroPrice']) ?
            $this->setShippingProtectionPrice() : $this->setShippingProtectionpriceZero();

        (isset($conditions['shippingProtectionHasNonZeroBasePrice']) && $conditions['shippingProtectionHasNonZeroBasePrice']) ?
            $this->setShippingProtectionBasePrice() : $this->setShippingProtectionBasePriceZero();

        (isset($conditions['shippingProtectionExists']) && $conditions['shippingProtectionExists']) ?
            $this->setShippingProtection() : $this->setShippingProtectionNull();
    }

    private function setExtensionAttributes(): void
    {
        $this->quoteMock->method('getExtensionAttributes')->willReturn($this->cartExtensionMock);
        $this->quoteMock->method('getId')->willReturn(72);
    }

    private function setExtensionAttributesNull(): void
    {
        $this->quoteMock->method('getExtensionAttributes')->willReturn(null);
    }

    private function setShippingProtectionPrice(): void
    {
        $this->shippingProtectionMock->expects($this->any())->method('getPrice')->willReturn($this->shippingProtectionPrice);
    }

    private function setShippingProtectionPriceZero(): void
    {
        $this->shippingProtectionMock->expects($this->any())->method('getPrice')->willReturn(0.0);
    }

    private function setShippingProtectionBasePrice(): void
    {
        $this->shippingProtectionMock->expects($this->any())->method('getBase')->willReturn($this->shippingProtectionBasePrice);
    }

    private function setShippingProtectionBasePriceZero(): void
    {
        $this->shippingProtectionMock->expects($this->any())->method('getBase')->willReturn(0.0);
    }


    private function setShippingProtection(): void
    {
        $this->cartExtensionMock->expects($this->any())->method('getShippingProtection')->willReturn($this->shippingProtectionMock);
    }

    private function setShippingProtectionNull(): void
    {
        $this->cartExtensionMock->expects($this->any())->method('getShippingProtection')->willReturn(null);
    }
}
