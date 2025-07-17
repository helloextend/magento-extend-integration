<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use Extend\Integration\ViewModel\EnvironmentAndExtendStoreUuid;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\Store;
use Magento\Directory\Model\Currency;
use Extend\Integration\Service\Extend;

class EnvironmentAndExtendStoreUuidTest extends TestCase
{
    /** @var EnvironmentAndExtendStoreUuid */
    private $environmentAndExtendStoreUuid;

    /** @var StoreIntegrationRepositoryInterface */
    private $storeIntegrationRepositoryMock;

    /** @var StoreManagerInterface */
    private $storeManagerMock;

    /** @var ScopeConfigInterface */
    private $scopeConfigMock;

    /** @var ActiveEnvironmentURLBuilder */
    private $activeEnvironmentURLBuilderMock;

    /** @var LoggerInterface */
    private $loggerMock;

    /** @var Http */
    private $requestMock;

    /** @var Store */
    private $storeMock;

    /** @var Currency */
    private $currencyMock;

    /** @var Extend */
    private $extendServiceMock;

    /** @var string */
    private $defaultCurrency = 'USD';

    protected function setUp(): void
    {
        $this->storeIntegrationRepositoryMock = $this->createStub(StoreIntegrationRepositoryInterface::class);
        $this->storeManagerMock = $this->createStub(StoreManagerInterface::class);
        $this->scopeConfigMock = $this->createStub(ScopeConfigInterface::class);
        $this->activeEnvironmentURLBuilderMock = $this->createStub(ActiveEnvironmentURLBuilder::class);
        $this->loggerMock = $this->createStub(LoggerInterface::class);
        $this->requestMock = $this->createStub(Http::class);
        $this->extendServiceMock = $this->createStub(Extend::class);

        $this->storeMock = $this->createStub(Store::class);
        $this->currencyMock = $this->createStub(Currency::class);

        $this->storeMock->method('getCurrentCurrency')->willReturn($this->currencyMock);
        $this->storeMock->method('getBaseCurrency')->willReturn($this->currencyMock);
        $this->storeManagerMock->method('getStore')->willReturn($this->storeMock);

        $this->environmentAndExtendStoreUuid = new EnvironmentAndExtendStoreUuid(
            $this->storeIntegrationRepositoryMock,
            $this->storeManagerMock,
            $this->scopeConfigMock,
            $this->activeEnvironmentURLBuilderMock,
            $this->loggerMock,
            $this->requestMock,
            $this->extendServiceMock
        );
    }

    public function testGetCurrencyCodeReturnsStoreCurrency()
    {
        $this->currencyMock->method('getCode')->willReturn($this->defaultCurrency);
        $this->assertEquals($this->defaultCurrency, $this->environmentAndExtendStoreUuid->getCurrencyCode());
    }

    public function testIsCurrencySupportedReturnsTrueWhenSelectedCurrencyMatchesBaseCurrency()
    {
        $this->currencyMock->method('getCode')->willReturn($this->defaultCurrency);
        $this->assertTrue($this->environmentAndExtendStoreUuid->isCurrencySupported());
    }

    public function testIsCurrencySupportedReturnsFalseWhenSelectedCurrencyDoesNotMatchBaseCurrency()
    {
        $this->currencyMock->method('getCode')->willReturnOnConsecutiveCalls('USD', 'GBP');
        $this->assertFalse($this->environmentAndExtendStoreUuid->isCurrencySupported());
    }

    public function testIsCurrencySupportedReturnsFalseWhenBaseCurrencyIsNotSupported()
    {
        $this->currencyMock->method('getCode')->willReturnOnConsecutiveCalls('JPY', 'JPY');
        $this->assertFalse($this->environmentAndExtendStoreUuid->isCurrencySupported());
    }

    public function testIsExtendProductProtectionEnabledReturnsTrueWhenBothExtendAndProductProtectionEnabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_PRODUCT_PROTECTION)
            ->willReturn('1');

        $this->assertTrue($this->environmentAndExtendStoreUuid->isExtendProductProtectionEnabled());
    }

    public function testIsExtendProductProtectionEnabledReturnsFalseWhenExtendDisabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(false);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_PRODUCT_PROTECTION)
            ->willReturn('1');

        $this->assertFalse($this->environmentAndExtendStoreUuid->isExtendProductProtectionEnabled());
    }

    public function testIsExtendProductProtectionEnabledReturnsFalseWhenProductProtectionDisabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_PRODUCT_PROTECTION)
            ->willReturn('0');

        $this->assertFalse($this->environmentAndExtendStoreUuid->isExtendProductProtectionEnabled());
    }

    public function testIsExtendShippingProtectionEnabledReturnsTrueWhenBothExtendAndShippingProtectionEnabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_SHIPPING_PROTECTION)
            ->willReturn('1');

        $this->assertTrue($this->environmentAndExtendStoreUuid->isExtendShippingProtectionEnabled());
    }

    public function testIsExtendShippingProtectionEnabledReturnsFalseWhenExtendDisabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(false);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_SHIPPING_PROTECTION)
            ->willReturn('1');

        $this->assertFalse($this->environmentAndExtendStoreUuid->isExtendShippingProtectionEnabled());
    }

    public function testIsExtendShippingProtectionEnabledReturnsFalseWhenShippingProtectionDisabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_SHIPPING_PROTECTION)
            ->willReturn('0');

        $this->assertFalse($this->environmentAndExtendStoreUuid->isExtendShippingProtectionEnabled());
    }

    public function testIsCartBalancingEnabledReturnsTrueWhenBothExtendAndCartBalancingEnabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_CART_BALANCING)
            ->willReturn('1');

        $this->assertTrue($this->environmentAndExtendStoreUuid->isCartBalancingEnabled());
    }

    public function testIsCartBalancingEnabledReturnsFalseWhenExtendDisabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(false);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_CART_BALANCING)
            ->willReturn('1');

        $this->assertFalse($this->environmentAndExtendStoreUuid->isCartBalancingEnabled());
    }

    public function testIsCartBalancingEnabledReturnsFalseWhenCartBalancingDisabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(true);
        $this->scopeConfigMock->method('getValue')
            ->with(Extend::ENABLE_CART_BALANCING)
            ->willReturn('0');

        $this->assertFalse($this->environmentAndExtendStoreUuid->isCartBalancingEnabled());
    }
}
