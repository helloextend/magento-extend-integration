<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Sales\Order\Invoice\Total;

use Extend\Integration\Api\Data\ShippingProtectionInterface;
use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Model\ShippingProtectionFactory;
use Extend\Integration\Model\ShippingProtectionTotalRepository;
use Magento\Sales\Model\Order\Invoice;

class ShippingProtection extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private ShippingProtectionFactory $shippingProtectionFactory;

    /**
     * @param ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
     */
    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        ShippingProtectionFactory $shippingProtectionFactory
    ) {
        parent::__construct();
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
    }

    /**
     * Get the Shipping Protection total for the invoice,
     * also checks if Shipping Protection has already been invoiced in this order,
     * or if the invoice only contains non-shippable items.
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice): ShippingProtection
    {
        $invoice->setOmitSp(false);

        if (($shippingProtection = $invoice->getExtensionAttributes()->getShippingProtection()) &&
            $invoice->getOrderId()
        ) {
            // Check if Shipping Protection has already been invoiced in this order
            foreach ($invoice
                    ->getOrder()
                    ->getInvoiceCollection()
                    ->getAllIds()
            as $invoiceId) {
                $existingInvoiceSp = $this->shippingProtectionTotalRepository->get(
                  $invoiceId,
                  ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID
                );
                $isExistingInvoiceSpValid = $existingInvoiceSp->getId() && (
                  $existingInvoiceSp->getShippingProtectionBasePrice() > 0
                  || $existingInvoiceSp->getOfferType() === ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE
                );
                if ($isExistingInvoiceSpValid) {
                    $invoice->setOmitSp(true);
                    return $this;
                }
            }

            // Check if the invoice only contains non-shippable items
            foreach ($invoice->getAllItems() as $item) {
                if ((int) $item->getQty() > 0 && $item->getOrderItem()->getIsVirtual() == '0') {
                    $shippingProtectionBasePrice = $shippingProtection->getBase();
                    $shippingProtectionPrice = $shippingProtection->getPrice();
                    $shippingProtectionTax = $shippingProtection->getShippingProtectionTax() ?? 0.0;

                    $invoice->setBaseShippingProtection($shippingProtectionBasePrice);
                    $invoice->setShippingProtection($shippingProtectionPrice);
                    $invoice->setShippingProtectionTax($shippingProtectionTax);

                    $invoice->setGrandTotal(
                        $invoice->getGrandTotal() + $invoice->getShippingProtection() + $invoice->getShippingProtectionTax()
                    );
                    $invoice->setBaseGrandTotal(
                        $invoice->getBaseGrandTotal() + $invoice->getBaseShippingProtection() + $invoice->getShippingProtectionTax()
                    );
                    $invoice->setTaxAmount($invoice->getTaxAmount() + $invoice->getShippingProtectionTax());
                    $invoice->setBaseTaxAmount(
                        $invoice->getBaseTaxAmount() + $invoice->getShippingProtectionTax()
                    );

                    // Return early since we've found a shippable item in the invoice
                    return $this;
                }
            }

            // If we reach this point, the invoice only contains non-shippable items
            // so shipping protection will not be associated with this invoice
            $invoice->setOmitSp(true);
            return $this;
        } elseif ($shippingProtection = $invoice->getExtensionAttributes()->getShippingProtection()
        ) {
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getIsVirtual() == '0') {
                    $shippingProtectionBasePrice = $shippingProtection->getBase();
                    $shippingProtectionPrice = $shippingProtection->getPrice();
                    $shippingProtectionTax = $shippingProtection->getShippingProtectionTax() ?? 0.0;

                    $invoice->setBaseShippingProtection($shippingProtectionBasePrice);
                    $invoice->setShippingProtection($shippingProtectionPrice);
                    $invoice->setShippingProtectionTax($shippingProtectionTax);

                    $invoice->setGrandTotal(
                        $invoice->getGrandTotal() + $invoice->getShippingProtection() + $invoice->getShippingProtectionTax()
                    );
                    $invoice->setBaseGrandTotal(
                        $invoice->getBaseGrandTotal() + $invoice->getBaseShippingProtection() + $invoice->getShippingProtectionTax()
                    );
                    $invoice->setTaxAmount($invoice->getTaxAmount() + $invoice->getShippingProtectionTax());
                    $invoice->setBaseTaxAmount(
                        $invoice->getBaseTaxAmount() + $invoice->getShippingProtectionTax()
                    );
                    return $this;
                }
            }
        }

        return $this;
    }
}
