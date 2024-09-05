<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service;

use Extend\Integration\Service\Extend as ExtendService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Api\Data\StoreInterface as Store;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ExtendTest extends TestCase
{

  /**
   * @var ExtendService | MockObject
   */
    private $testSubject;

  /**
   * @var ScopeConfigInterface | MockObject
   */
    private $scopeConfigMock;

  /**
   * @var StoreManagerInterface | MockObject
   */
    private $storeManagerMock;

    /**
     * @var Store | MockObject
     */
    private $store;

    private $storeCode = 'default';

    protected function setUp(): void
    {
        $this->store = $this->createConfiguredMock(Store::class, [
            'getCode' => $this->storeCode,
        ]);
        $this->storeManagerMock = $this->createStub(StoreManagerInterface::class);
        $this->storeManagerMock->method('getStore')->willReturn($this->store);
        $this->scopeConfigMock = $this->createStub(ScopeConfigInterface::class);
        $this->testSubject = new ExtendService(
            $this->scopeConfigMock,
            $this->storeManagerMock
        );
    }

    public function testIsProductProtectionSkuFalseWithArbitrarySku()
    {
        $this->assertFalse($this->testSubject->isProductionProtectionSku('ABC123'));
    }

    public function testIsProductProtectionSkuTrueWithLegacyExtendProductProtectionSku()
    {
        $this->assertTrue($this->testSubject->isProductionProtectionSku(ExtendService::WARRANTY_PRODUCT_LEGACY_SKU));
    }

    public function testIsProductProtectionSkuTrueWithExtendProductProtectionSku()
    {
        $this->assertTrue($this->testSubject->isProductionProtectionSku(ExtendService::WARRANTY_PRODUCT_SKU));
    }

    public function testIsEnabledReturnsTrueIfExtendIsEnabled()
    {
        $this->scopeConfigMock->method('getValue')->willReturnMap([
          [ExtendService::ENABLE_EXTEND, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '1'],
        ]);
        $this->assertTrue($this->testSubject->isEnabled());
    }

    public function testIsEnabledReturnsFalseIfExtendIsDisabled()
    {
        $this->scopeConfigMock->method('getValue')->willReturnMap([
          [ExtendService::ENABLE_EXTEND, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '0'],
        ]);
        $this->assertFalse($this->testSubject->isEnabled());
    }

    public function testIsProductProtectionEnabledReturnsTrueIfExtendIsEnabledAndProductProtectionIsEnabled()
    {
        $this->scopeConfigMock->method('getValue')->willReturnMap([
          [ExtendService::ENABLE_EXTEND, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '1'],
          [ExtendService::ENABLE_PRODUCT_PROTECTION, ScopeInterface::SCOPE_STORE, $this->storeCode, '1'],
        ]);
        $this->assertTrue($this->testSubject->isProductProtectionEnabled());
    }

    public function testIsProductProtectionEnabledReturnsFalseIfExtendIsDisabled()
    {
        $this->scopeConfigMock->method('getValue')->willReturnMap([
          [ExtendService::ENABLE_EXTEND, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '0'],
          [ExtendService::ENABLE_PRODUCT_PROTECTION, ScopeInterface::SCOPE_STORE, $this->storeCode, '1'],
        ]);
        $this->assertFalse($this->testSubject->isProductProtectionEnabled());
    }

    public function testIsProductProtectionEnabledReturnsFalseIfExtendIsEnabledButProductProtectionIsDisabled()
    {
        $this->scopeConfigMock->method('getValue')->willReturnMap([
          [ExtendService::ENABLE_EXTEND, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '1'],
          [ExtendService::ENABLE_PRODUCT_PROTECTION, ScopeInterface::SCOPE_STORE, $this->storeCode, '0'],
        ]);
        $this->assertFalse($this->testSubject->isProductProtectionEnabled());
    }

    public function testIsProductProtectionEnabledReturnsFalseIfExtendAndProductProtectionAreExplicitlyDisabled()
    {
        $this->scopeConfigMock->method('getValue')->willReturnMap([
          [ExtendService::ENABLE_EXTEND, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, '0'],
          [ExtendService::ENABLE_PRODUCT_PROTECTION, ScopeInterface::SCOPE_STORE, $this->storeCode, '0'],
        ]);
        $this->assertFalse($this->testSubject->isProductProtectionEnabled());
    }
}
