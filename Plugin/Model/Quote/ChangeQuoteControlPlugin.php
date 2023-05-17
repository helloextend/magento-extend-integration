<?php

namespace Extend\Integration\Plugin\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;

class ChangeQuoteControlPlugin
{
    public function aroundIsAllowed(
        \Magento\Quote\Model\ChangeQuoteControl $subject,
        callable $proceed,
        CartInterface $quote
    ): bool {
        if ($quote->getData('_xtd_is_extend_quote_save') === true) {
            unset($quote['_xtd_is_extend_quote_save']);
            return true;
        }
        return $proceed($quote);
    }
}
