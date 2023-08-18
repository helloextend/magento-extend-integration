<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Cart;

use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Service\Extend;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\TotalSegmentExtensionFactory;
use Magento\Quote\Model\Cart\TotalsConverter;

class TotalsConverterPlugin
{
    private Session $checkoutSession;
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private Extend $extend;
    private TotalSegmentExtensionFactory $totalSegmentExtensionFactory;

    public function __construct(
        Session $checkoutSession,
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        Extend $extend,
        TotalSegmentExtensionFactory $totalSegmentExtensionFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->extend = $extend;
        $this->totalSegmentExtensionFactory = $totalSegmentExtensionFactory;
    }

    /**
     * @param TotalsConverter $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterProcess(\Magento\Quote\Model\Cart\TotalsConverter $subject, $result)
    {
        if (!$this->extend->isEnabled())
            return $result;

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
