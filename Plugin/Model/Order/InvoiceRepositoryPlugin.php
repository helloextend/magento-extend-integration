<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Order;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\OrderRepository;
use Extend\Integration\Service\Extend;

class InvoiceRepositoryPlugin
{
    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    /**
     * @var InvoiceExtensionFactory
     */
    private InvoiceExtensionFactory $invoiceExtension;

    private Extend $extend;

    /**
     * @param ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
     * @param InvoiceExtensionFactory $invoiceExtension
     */
    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        InvoiceExtensionFactory $invoiceExtension,
        Extend $extend
    ) {

        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->invoiceExtension = $invoiceExtension;
        $this->extend = $extend;
    }

    /**
     * This plugin injects the Shipping Protection record into the invoice's extension attributes if a matching record is found with a given invoice ID
     *
     * @param InvoiceRepository $subject
     * @param $result
     * @param $invoiceId
     * @return mixed
     */
    public function afterGet(\Magento\Sales\Model\Order\InvoiceRepository $subject, $result, $invoiceId)
    {
        if (!$this->extend->isEnabled()) {
            return $result;
        }

        $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
            $invoiceId,
            ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID,
            $result
        );

        return $result;
    }

    /**
     * This save the Shipping Protection data from the invoice's extension attributes into the Shipping Protection totals table, saving the entity type and invoice ID as well
     *
     * @param InvoiceRepository $subject
     * @param $result
     * @param $invoice
     * @return mixed
     */
    public function afterSave(\Magento\Sales\Model\Order\InvoiceRepository $subject, $result, $invoice)
    {
        if (!$this->extend->isEnabled()) {
            return $result;
        }

        $extensionAttributes = $invoice->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->invoiceExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        if ($result && !$result->getOmitSp() && $shippingProtection) {
            $this->shippingProtectionTotalRepository->saveAndResaturateExtensionAttribute(
                $shippingProtection,
                $result,
                ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID
            );
        }
        return $result;
    }
}
