<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OrderLogLevel implements OptionSourceInterface
{
    public const ERRORS_ONLY = 'errors_only';
    public const PAYLOADS_AND_ERRORS = 'payloads_and_errors';
    public const VERBOSE = 'verbose';

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::ERRORS_ONLY, 'label' => __('Errors Only')],
            ['value' => self::PAYLOADS_AND_ERRORS, 'label' => __('Order Payloads and Errors')],
            ['value' => self::VERBOSE, 'label' => __('Verbose (Payloads, Errors, and API Responses)')],
        ];
    }
}
