<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Cart;

use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Checkout\Model\Session;

class TotalsConverterPlugin
{
    private Session $checkoutSession;
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    public function __construct(
        Session $checkoutSession,
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
    }

    public function afterProcess(\Magento\Quote\Model\Cart\TotalsConverter $subject, $result)
    {
        if (isset($result['shipping_protection'])) {
            $quoteId = $this->checkoutSession->getQuote()->getId();
            if ($quoteId) {
                $spQuoteId = $this->shippingProtectionTotalRepository
                    ->get(
                        $quoteId,
                        \Extend\Integration\Api\Data\ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID
                    )
                    ->getSpQuoteId();
            }

            $attributes = $result['shipping_protection']->getExtensionAttributes();
            if ($attributes === null) {
                $attributes = $this->totalSegmentExtensionFactory->create();
            }
            if ($spQuoteId) {
                $attributes->setSpQuoteId($spQuoteId);
                $result['shipping_protection']->setExtensionAttributes($attributes);
            }
        }
        return $result;
    }
}
