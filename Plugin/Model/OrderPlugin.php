<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;

class OrderPlugin
{

    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
    }

    public function afterGetInvoiceCollection(\Magento\Sales\Model\Order $subject, $result)
    {
        foreach ($result->getItems() as $invoice) {
            if ($invoice->getId()) {
                $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                    $invoice->getId(),
                    ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID,
                    $invoice
                );
            }
        }

        return $result;
    }

    public function afterGetCreditmemosCollection(\Magento\Sales\Model\Order $subject, $result)
    {
        foreach ($result->getItems() as $creditmemo) {
            if ($creditmemo->getId()) {
                $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                    $creditmemo->getId(),
                    ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID,
                    $creditmemo
                );
            }
        }

        return $result;
    }
}
