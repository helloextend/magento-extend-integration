<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Preference\Framework\Http;

use Extend\Integration\Preference\Framework\Http\ZendClientPreference;
use Extend\Integration\Test\Utils\PHPUnitUtils;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ZendClientPreferenceTest extends TestCase
{
    public function testAdapterHookLeavesDeprecatedClientUnchanged(): void
    {
        if (method_exists(ZendClientPreference::class, 'getUri')) {
            $this->markTestSkipped('The parent client provides the legacy HTTP adapter API.');
        }

        $client = (new ReflectionClass(ZendClientPreference::class))->newInstanceWithoutConstructor();

        $this->assertSame($client, PHPUnitUtils::callMethod($client, '_trySetCurlAdapter'));
    }
}
