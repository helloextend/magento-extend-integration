<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Mock;

use Extend\Integration\Model\ShippingProtection;
use Extend\Integration\Api\Data\ShippingProtectionInterface;

/**
 * This interface can be used to create mocks for classes that rely on magic methods.
 * Because PHPUnit v10+ deprecates the use of addMethods, and otherwise complains that
 * the magic methods do not exist on the class, this interface can be used to create
 * a mock that will return the expected values for the magic methods.
 *
 * @link https://github.com/sebastianbergmann/phpunit/issues/5320#issuecomment-2368024251
 */
interface MagicMockInterface
{
    /**
     * @return T
     */
    public function __invoke();

    /**
     * This method can be used with a willReturnCallback to return a specific value
     * for a given method that may not be defined on this interface.
     *
     * @return T
     * @example
     * $mock = $this->createMock(MagicMockInterface::class);
     * $mock->expects($this->any())
     *      ->method('__call')
     *      ->willReturnCallback(fn (string $name, array $arguments): string => match ($name) {
     *          'a' => 'foo',
     *          'b' => 'bar',
     *          default => throw new Exception('Method not found'),
     *      });
     */
    public function __call(string $name, array $arguments);

    /**
     * @return void
     */
    public function setShippingProtection(array $shippingProtection);

    /**
     * @return ShippingProtection|ShippingProtectionInterface|array|null
     */
    public function getShippingProtection();

    /**
     * @return bool
     */
    public function getOmitSp(): bool;

    /**
     * @return bool
     */
    public function getSpgSpRemovedFromCreditMemo(): bool;
}
