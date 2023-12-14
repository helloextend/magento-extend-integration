<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Directory\Controller\Currency;

use Magento\Directory\Controller\Currency\SwitchAction as SwitchActionParent;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Extend\Integration\Service\Extend;
use Extend\Integration\Model\ShippingProtectionTotalRepository;

class SwitchAction
{
    /** @var Session */
    private $checkoutSession;

    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var ShippingProtectionTotalRepository */
    private $shippingProtectionTotalRepository;

    /**
     * SwitchAction constructor. This allows us to observe when a currency switch occurs
     * and take action if required.
     *
     * @param Session $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param ShippingProtectionTotalRepository $shippingProtectionTotalRepository
     */
    public function __construct(
        Session $checkoutSession,
        CartRepositoryInterface $cartRepository,
        ShippingProtectionTotalRepository $shippingProtectionTotalRepository
    ) {
          $this->checkoutSession = $checkoutSession;
          $this->cartRepository = $cartRepository;
          $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
    }

    /**
     * Remove any Extend products from the cart as new offers would need to be fetched (if applicable).
     *
     * @param SwitchActionParent $subject
     * @return void
     */
    public function beforeExecute(SwitchActionParent $subject)
    {
        // Remove any shipping protection totals
        $this->shippingProtectionTotalRepository->delete();

        // Get the current checkout session
        $quote = $this->checkoutSession->getQuote();
        $shouldSaveQuote = false;
        $items = $quote->getItems();

        if (empty($items)) {
            return;
        }

        // Remove any Extend products from the cart
        foreach ($items as $item) {
            if (Extend::isProductionProtectionSku($item->getSku())) {
                $quote->removeItem($item->getId());
                $shouldSaveQuote = true;
            }
        }

        if ($shouldSaveQuote) {
            $this->cartRepository->save($quote->collectTotals());
        }
    }
}
