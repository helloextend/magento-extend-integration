<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Sales\Order\Creditmemo\Total;

use Extend\Integration\Api\Data\ShippingProtectionInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Extend\Integration\Model\ShippingProtectionFactory;

class ShippingProtection extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private ShippingProtectionFactory $shippingProtectionFactory;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        ShippingProtectionFactory $shippingProtectionFactory
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
    }

    /**
     * Get the Shipping Protection total from the credit memo extension attributes,
     * zero it out if some or all of the order has already been shipped.
     *
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo): ShippingProtection
    {
        // Initialize to not omit the inclusion of SP in the credit memo
        $creditmemo->setOmitSp(false);

        // Check for credit memos which have already refunded shipping protection
        if ($shippingProtection = $creditmemo->getExtensionAttributes()->getShippingProtection()) {
            $shippingProtectionBasePrice = $shippingProtection->getBase();
            $shippingProtectionPrice = $shippingProtection->getPrice();

            $existingCreditMemoWithSPCount = 0;
            $existingCreditMemos = $creditmemo
                ->getOrder()
                ->getCreditmemosCollection()
                ->getItems();
            if ($existingCreditMemos) {
                foreach ($existingCreditMemos as $existingCreditMemo) {
                    if ($shippingProtectionEntity = $this->shippingProtectionTotalRepository->get(
                        $existingCreditMemo->getId(),
                        \Extend\Integration\Api\Data\ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID
                    )) {
                        // SP entity attached to existing credit memos are valid if they have a price greater than 0 or are SPG
                        if ($shippingProtectionEntity->getData() && (
                          $shippingProtectionEntity->getShippingProtectionPrice() > 0
                          || $shippingProtectionEntity->getOfferType() === ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE
                        )) {
                            $existingCreditMemoWithSPCount = 1;
                            break;
                        }
                    }
                }
            }

            if (count($creditmemo->getOrder()->getShipmentsCollection()) === 0 &&
                $existingCreditMemoWithSPCount === 0
            ) {
                // If the shipping protection tax amount is set we know the store/order has/had sp taxability enabled, otherwise this returns null and we set to 0.
                $shippingProtectionTax = $shippingProtection->getShippingProtectionTax() ?? 0.0;

                // SP is being refunded if the price is greater than 0 or if it's a SPG and hasn't been removed from the credit memo
                $isRefundingSP = $shippingProtectionPrice > 0 || (
                  $shippingProtection->getOfferType() === ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE
                  && !$creditmemo->getSpgSpRemovedFromCreditMemo()
                );

                // Default SP totals and tax to 0. We'll override in specific scenarios below.
                $spGrandTotalToAdd = 0.0;
                $spBaseGrandTotalToAdd = 0.0;
                $spTaxAmountToAdd = 0.0;
                $spBaseTaxAmountToAdd = 0.0;

                /**
                 * Some scenarios require us to manually adjust the totals and tax amounts because Magento won't automatically handle every case.
                 */
                if ($creditmemo->isLast() && $isRefundingSP) { // First credit memo and refunding shipping protection.
                    // Magento will use the original order total & tax which already included SP tax, we only need to add SP to the total.
                    $spGrandTotalToAdd = $shippingProtectionPrice;
                    $spBaseGrandTotalToAdd = $shippingProtectionBasePrice;
                } elseif ($creditmemo->isLast() && !$isRefundingSP) { // First credit memo and not refunding shipping protection.
                    // We need to manually remove the SP tax from the total and tax amount.
                    $spGrandTotalToAdd = -$shippingProtectionTax;
                    $spBaseGrandTotalToAdd = -$shippingProtectionTax;
                    $spTaxAmountToAdd = -$shippingProtectionTax;
                    $spBaseTaxAmountToAdd = -$shippingProtectionTax;
                } elseif (!$creditmemo->isLast() && $isRefundingSP) { // Not the first credit memo and refunding shipping protection.
                    // We need to manually add SP and SP tax to the totals and tax amounts.
                    $spGrandTotalToAdd = $shippingProtectionPrice + $shippingProtectionTax;
                    $spBaseGrandTotalToAdd = $shippingProtectionBasePrice + $shippingProtectionTax;
                    $spTaxAmountToAdd = $shippingProtectionTax;
                    $spBaseTaxAmountToAdd = $shippingProtectionTax;
                }

                // Set the Shipping Protection totals and tax amounts.
                $creditmemo->setShippingProtection($isRefundingSP ? $shippingProtectionPrice : 0.0);
                $creditmemo->setBaseShippingProtection($isRefundingSP ? $shippingProtectionBasePrice : 0.0);
                $creditmemo->setShippingProtectionTax($isRefundingSP ? $shippingProtectionTax : 0.0);

                // Update the credit memo totals and tax amounts, which may include SP changes depending on the scenario.
                $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $spGrandTotalToAdd);
                $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $spBaseGrandTotalToAdd);
                $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $spTaxAmountToAdd);
                $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $spBaseTaxAmountToAdd);
            } else {
                // An existing credit memo has already refunded shipping protection, so we skip any displaying or refunding of SP for this credit memo
                $creditmemo->setOmitSp(true);
            }
        }

        return $this;
    }
}
