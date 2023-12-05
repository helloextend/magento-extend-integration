<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Service;

use Extend\Integration\Service\Extend;
use PHPUnit\Framework\TestCase;

class ExtendTest extends TestCase
{

    protected function setUp(): void
    {
    }

    public function testIsProductProtectionSkuFalseWithArbitrarySku()
    {
      $this->assertFalse(Extend::isProductionProtectionSku('ABC123'));
    }

    public function testIsProductProtectionSkuTrueWithLegacyExtendProductProtectionSku()
    {
      $this->assertTrue(Extend::isProductionProtectionSku(Extend::WARRANTY_PRODUCT_LEGACY_SKU));
    }

    public function testIsProductProtectionSkuTrueWithExtendProductProtectionSku()
    {
      $this->assertTrue(Extend::isProductionProtectionSku(Extend::WARRANTY_PRODUCT_SKU));
    }
}
