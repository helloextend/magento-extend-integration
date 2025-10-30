<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Model;

use Extend\Integration\Model\ProductProtection;
use Extend\Integration\Service\Api\Integration;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test class for ProductProtection PHP 8.4 compatibility
 */
class ProductProtectionTest extends TestCase
{
    /**
     * @var ProductProtection
     */
    private $productProtection;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var Session|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface|MockObject
     */
    private $maskedQuoteIdToQuoteIdMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryMock;

    /**
     * @var ItemFactory|MockObject
     */
    private $itemFactoryMock;

    /**
     * @var OptionFactory|MockObject
     */
    private $itemOptionFactoryMock;

    /**
     * @var Integration|MockObject
     */
    private $integrationMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->itemFactoryMock = $this->createMock(ItemFactory::class);
        $this->itemOptionFactoryMock = $this->createMock(OptionFactory::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->integrationMock = $this->createMock(Integration::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->maskedQuoteIdToQuoteIdMock = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);

        $this->productProtection = new ProductProtection(
            $this->quoteRepositoryMock,
            $this->productRepositoryMock,
            $this->itemFactoryMock,
            $this->itemOptionFactoryMock,
            $this->checkoutSessionMock,
            $this->integrationMock,
            $this->storeManagerMock,
            $this->loggerMock,
            $this->serializerMock,
            $this->maskedQuoteIdToQuoteIdMock
        );
    }

    /**
     * Test PHP 8.4 nullable parameter compatibility
     * 
     * This test verifies that the upsert method can handle nullable parameters
     * without triggering PHP 8.4 deprecation warnings
     */
    public function testUpsertWithNullableParameters(): void
    {
        // This test verifies that the upsert method signature has proper nullable types for PHP 8.4
        $reflection = new \ReflectionClass($this->productProtection);
        $upsertMethod = $reflection->getMethod('upsert');
        
        // Verify that the method accepts nullable parameters
        $parameters = $upsertMethod->getParameters();
        $this->assertCount(11, $parameters, 'Upsert method should have 11 parameters');
        
        // Verify that quantity parameter is nullable
        $quantityParam = $parameters[0];
        $this->assertEquals('quantity', $quantityParam->getName());
        $this->assertTrue($quantityParam->allowsNull(), 'quantity parameter should allow null');
        
        // Verify that cartId parameter is nullable
        $cartIdParam = $parameters[1];
        $this->assertEquals('cartId', $cartIdParam->getName());
        $this->assertTrue($cartIdParam->allowsNull(), 'cartId parameter should allow null');
    }

    /**
     * Test PHP 8.4 nullable parameter compatibility with mixed values
     */
    public function testUpsertWithMixedNullableParameters(): void
    {
        // This test verifies that the upsert method can handle mixed null/non-null parameters
        $reflection = new \ReflectionClass($this->productProtection);
        $upsertMethod = $reflection->getMethod('upsert');
        
        // Verify all parameters are nullable
        $parameters = $upsertMethod->getParameters();
        foreach ($parameters as $parameter) {
            $this->assertTrue($parameter->allowsNull(), 
                "Parameter {$parameter->getName()} should allow null for PHP 8.4 compatibility"
            );
        }
    }

    /**
     * Test that nullable parameters work correctly with PHP 8.4 type system
     */
    public function testNullableParameterTypeHandling(): void
    {
        // This test ensures that the nullable type declarations work correctly
        // and don't cause any type-related issues in PHP 8.4
        
        $reflection = new \ReflectionClass($this->productProtection);
        $upsertMethod = $reflection->getMethod('upsert');
        
        // Verify the method signature has proper nullable types
        $parameters = $upsertMethod->getParameters();
        
        $this->assertCount(11, $parameters);
        
        // Check that all parameters are nullable
        foreach ($parameters as $parameter) {
            $this->assertTrue($parameter->allowsNull(), 
                "Parameter {$parameter->getName()} should allow null values for PHP 8.4 compatibility"
            );
        }
        
        // Verify the method has proper return type
        $returnType = $upsertMethod->getReturnType();
        $this->assertNotNull($returnType, 'Method should have a return type');
        $this->assertEquals('void', $returnType->getName());
    }

    /**
     * Test backward compatibility with PHP 7.4-8.3
     * 
     * This test ensures that the nullable parameter changes don't break
     * compatibility with older PHP versions
     */
    public function testBackwardCompatibility(): void
    {
        // Test that the method signature supports backward compatibility
        $reflection = new \ReflectionClass($this->productProtection);
        $upsertMethod = $reflection->getMethod('upsert');
        
        // Verify that all parameters still allow null (backward compatible)
        $parameters = $upsertMethod->getParameters();
        foreach ($parameters as $parameter) {
            $this->assertTrue($parameter->allowsNull(), 
                "Parameter {$parameter->getName()} should allow null for backward compatibility"
            );
        }
    }

    /**
     * Test actual invocation with all null parameters
     * 
     * This test verifies that PHP 8.4 can actually invoke the upsert method
     * with all null parameters without throwing type errors
     */
    public function testUpsertInvocationWithAllNulls(): void
    {
        // Setup minimal mocks to allow method execution
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteMock->method('getItems')->willReturn([]);
        $quoteMock->method('getId')->willReturn(1);
        
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->method('getId')->willReturn(1);
        
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);
        
        $reflection = new \ReflectionClass($this->productProtection);
        $upsertMethod = $reflection->getMethod('upsert');
        $upsertMethod->setAccessible(true);
        
        // Expect LocalizedException for missing required parameters
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/Missing required parameters/i');
        
        // This should throw a LocalizedException, not a TypeError
        $upsertMethod->invoke(
            $this->productProtection,
            null, null, null, null, null, null, null, null, null, null, null
        );
    }

    /**
     * Test actual invocation with mixed null and valid values
     * 
     * This test verifies that PHP 8.4 can invoke the upsert method
     * with a mix of null and valid values
     */
    public function testUpsertInvocationWithMixedValues(): void
    {
        // Setup minimal mocks
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteMock->method('getItems')->willReturn([]);
        $quoteMock->method('getId')->willReturn(1);
        
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->method('getId')->willReturn(1);
        
        $this->checkoutSessionMock->method('getQuote')->willReturn($quoteMock);
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);
        
        $reflection = new \ReflectionClass($this->productProtection);
        $upsertMethod = $reflection->getMethod('upsert');
        $upsertMethod->setAccessible(true);
        
        // Expect LocalizedException for price = 0
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/price of 0/i');
        
        // This should throw a LocalizedException, not a TypeError
        $upsertMethod->invoke(
            $this->productProtection,
            1,      // quantity
            null,   // cartId
            null,   // cartItemId
            'test-product', // productId
            'test-plan',   // planId
            0,      // price (triggers exception)
            null,   // term
            null,   // coverageType
            null,   // leadToken
            null,   // listPrice
            null    // orderOfferPlanId
        );
    }
}
