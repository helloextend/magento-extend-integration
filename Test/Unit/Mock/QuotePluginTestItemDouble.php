<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Mock;

use Magento\Quote\Model\Quote\Item;

/**
 * getCustomPrice is a magic accessor on Quote\Item; declaring it as a real method lets it be mocked
 * via onlyMethods on both PHPUnit 9 and 12 (addMethods is gone in 12).
 */
class QuotePluginTestItemDouble extends Item
{
    public function getCustomPrice()
    {
        return null;
    }
}
