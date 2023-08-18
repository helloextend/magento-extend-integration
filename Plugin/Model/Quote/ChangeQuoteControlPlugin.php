<?php

namespace Extend\Integration\Plugin\Model\Quote;

use Extend\Integration\Service\Extend;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ChangeQuoteControl;

class ChangeQuoteControlPlugin
{
    private Extend $extend;

    public function __construct(
        Extend $extend
    ) {
        $this->extend = $extend;
    }

    /**
     * @param ChangeQuoteControl $subject
     * @param callable $proceed
     * @param CartInterface $quote
     * @return bool
     */
    public function aroundIsAllowed(
        \Magento\Quote\Model\ChangeQuoteControl $subject,
        callable $proceed,
        CartInterface $quote
    ): bool {
        if (!$this->extend->isEnabled())
            return $proceed($quote);

        if ($quote->getData('_xtd_is_extend_quote_save') === true) {
            unset($quote['_xtd_is_extend_quote_save']);
            return true;
        }
        return $proceed($quote);
    }
}
