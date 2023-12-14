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
            $this->requestMock
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
}
