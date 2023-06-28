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
        if (
            ($shippingProtection = $invoice->getExtensionAttributes()->getShippingProtection()) &&
            $invoice->getOrderId()
        ) {
            foreach (
                $invoice
                    ->getOrder()
                    ->getInvoiceCollection()
                    ->getAllIds()
                as $invoiceId
            ) {
                if (
                    $this->shippingProtectionTotalRepository->get(
                        $invoiceId,
                        ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID
                    ) &&
                    $this->shippingProtectionTotalRepository
                        ->get($invoiceId, ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID)
                        ->getShippingProtectionBasePrice() > 0
                ) {
                    $this->zeroOutShippingProtection($invoice, $shippingProtection);
                    return $this;
                }
            }

            foreach ($invoice->getAllItems() as $item) {
                if ((int) $item->getQty() > 0 && $item->getOrderItem()->getIsVirtual() == '0') {
                    $shippingProtectionBasePrice = $shippingProtection->getBase();
                    $shippingProtectionPrice = $shippingProtection->getPrice();

                    $invoice->setBaseShippingProtection($shippingProtectionBasePrice);
                    $invoice->setShippingProtection($shippingProtectionPrice);

                    $invoice->setGrandTotal(
                        $invoice->getGrandTotal() + $invoice->getShippingProtection()
                    );
                    $invoice->setBaseGrandTotal(
                        $invoice->getBaseGrandTotal() + $invoice->getBaseShippingProtection()
                    );
                    return $this;
                }
            }

            $this->zeroOutShippingProtection($invoice, $shippingProtection);
        } elseif (
            $shippingProtection = $invoice->getExtensionAttributes()->getShippingProtection()
        ) {
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getIsVirtual() == '0') {
                    $shippingProtectionBasePrice = $shippingProtection->getBase();
                    $shippingProtectionPrice = $shippingProtection->getPrice();

                    $invoice->setBaseShippingProtection($shippingProtectionBasePrice);
                    $invoice->setShippingProtection($shippingProtectionPrice);

                    $invoice->setGrandTotal(
                        $invoice->getGrandTotal() + $invoice->getShippingProtection()
                    );
                    $invoice->setBaseGrandTotal(
                        $invoice->getBaseGrandTotal() + $invoice->getBaseShippingProtection()
                    );
                    return $this;
                }
            }
        }

        return $this;
    }

    /**
     * If shipping protection cannot be applied because it's already been invoiced,
     * or the order only contains non-shippable items,
     * then we need to zero it out in the totals and the extension attribute,
     * which will persist to the database.
     *
     * @param Invoice $invoice
     * @param ShippingProtectionInterface $shippingProtection
     * @return void
     */
    private function zeroOutShippingProtection(
        Invoice $invoice,
        \Extend\Integration\Api\Data\ShippingProtectionInterface $shippingProtectionTotal
    ) {
        $invoice->setBaseShippingProtection(0.0);
        $invoice->setShippingProtection(0.0);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getShippingProtection());
        $invoice->setBaseGrandTotal(
            $invoice->getBaseGrandTotal() + $invoice->getBaseShippingProtection()
        );

        $shippingProtection = $this->shippingProtectionFactory->create();
        $shippingProtection->setBase(0.0);
        $shippingProtection->setBaseCurrency($shippingProtectionTotal->getBaseCurrency());
        $shippingProtection->setPrice(0.0);
        $shippingProtection->setCurrency($shippingProtectionTotal->getCurrency());
        $shippingProtection->setSpQuoteId($shippingProtectionTotal->getSpQuoteId());
        $extensionAttributes = $invoice->getExtensionAttributes();
        $extensionAttributes->setShippingProtection($shippingProtection);
        $invoice->setExtensionAttributes($extensionAttributes);
    }
}
