<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Fixture;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

abstract class BackendBlockTestCase extends TestCase
{
    /** @var ObjectManagerInterface|null */
    private $previousObjectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $property = $this->getObjectManagerProperty();
        $this->previousObjectManager = $property->getValue();
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnMap([
            [JsonHelper::class, $this->createStub(JsonHelper::class)],
            [DirectoryHelper::class, $this->createStub(DirectoryHelper::class)],
            [SecureHtmlRenderer::class, $this->createStub(SecureHtmlRenderer::class)],
        ]);
        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        $this->getObjectManagerProperty()->setValue(null, $this->previousObjectManager);
        parent::tearDown();
    }

    /**
     * Access the shared instance so tests restore the original application state.
     *
     * @return ReflectionProperty
     */
    private function getObjectManagerProperty(): ReflectionProperty
    {
        $property = new ReflectionProperty(ObjectManager::class, '_instance');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        return $property;
    }
}
