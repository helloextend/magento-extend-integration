<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Controller\Adminhtml\Order\Invoice;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Service\Extend;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Controller\Adminhtml\Order\Invoice\Save;

class SavePlugin
{
    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var InvoiceExtensionFactory
     */
    private InvoiceExtensionFactory $invoiceExtensionFactory;

    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private Extend $extend;

    /**
     * @param Registry $registry
     * @param InvoiceExtensionFactory $invoiceExtensionFactory
     * @param ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
     * @param Extend $extend
     */
    public function __construct(
        Registry $registry,
        InvoiceExtensionFactory $invoiceExtensionFactory,
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        Extend $extend
    ) {
        $this->registry = $registry;
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->extend = $extend;
    }

    /**
     * This save the Shipping Protection data from the invoice's extension attributes into the Shipping Protection totals table, saving the entity type and invoice ID as well
     *
     * @param Save $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(
        \Magento\Sales\Controller\Adminhtml\Order\Invoice\Save $subject,
        $result
    ) {
        if (!$this->extend->isEnabled())
            return $result;

        $invoice = $this->registry->registry('current_invoice');
        if ($invoice && !$invoice->getOmitSp()) {
          $invoiceExtensionAttributes = $invoice->getExtensionAttributes();
            if ($invoiceExtensionAttributes === null) {
                $invoiceExtensionAttributes = $this->invoiceExtensionFactory->create();
            }
            $shippingProtection = $invoiceExtensionAttributes->getShippingProtection();
            if ($result && $shippingProtection) {
                $this->shippingProtectionTotalRepository->saveAndResaturateExtensionAttribute(
                    $shippingProtection,
                    $invoice,
                    ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID
                );
            }
        }
        return $result;
    }
}
