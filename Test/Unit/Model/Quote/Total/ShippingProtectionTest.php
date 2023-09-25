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
    /**
     * @var array|array[]
     */
    private array $items;

    protected function setUp(): void
    {
        // class under test's constructor dependencies
        $this->shippingProtectionTotalRepositoryMock = $this->createStub(ShippingProtectionTotalRepositoryInterface::class);
        $this->serializerMock = $this->createStub(SerializerInterface::class);
        $this->cartExtensionMock = $this->createStub(CartExtension::class);
        $this->cartExtensionFactoryMock = $this->createConfiguredMock(
            CartExtensionFactory::class,
            ['create' => $this->cartExtensionMock]
        );

        // instantiate class under test
        $this->testSubject = new ShippingProtection(
            $this->shippingProtectionTotalRepositoryMock,
            $this->serializerMock,
            $this->cartExtensionFactoryMock
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
    )
    {
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
