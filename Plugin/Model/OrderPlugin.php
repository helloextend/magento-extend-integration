<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Sales\Model\Order;

class OrderPlugin
{
    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
    }

    /**
     * Saturate Invoice Collection Extension Attributes with SP values from database
     *
     * @param Order $subject
     * @param $result
     * @return mixed
     */
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

    /**
     * Saturate Creditmemo Collection Extension Attributes with SP values from database
     *
     * @param Order $subject
     * @param $result
     * @return mixed
     */
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

    /**
     * Saturate Order Extension Attributes with SP values from database on order success page
     *
     * @param Order $subject
     * @param $result
     * @param $incrementId
     * @return mixed
     */
    public function afterLoadByIncrementId(
        \Magento\Sales\Model\Order $subject,
        $result,
        $incrementId
    ) {
        if ($result->getId()) {
            $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                $result->getId(),
                ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID,
                $result
            );
        }

        return $result;
    }
}
