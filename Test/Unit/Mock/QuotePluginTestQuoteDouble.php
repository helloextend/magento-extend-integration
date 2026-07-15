<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Mock;

use Magento\Quote\Model\Quote;

/**
 * setTotalsCollectedFlag/getTotalsCollectedFlag are magic accessors on Quote; declaring them as
 * real methods lets them be mocked via onlyMethods on both PHPUnit 9 and 12 (addMethods is gone in 12).
 */
class QuotePluginTestQuoteDouble extends Quote
{
    public function setTotalsCollectedFlag($flag)
    {
        return $this;
    }

    public function getTotalsCollectedFlag()
    {
        return false;
    }
}
