<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Service\Extend;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;

class OrderPlugin
{
    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private Extend $extend;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        Extend $extend
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->extend = $extend;
    }

    /**
     * Saturate Invoice Collection Extension Attributes with SP values from database
     *
     * @param Order $subject
     * @param $result
     * @return mixed
     */
    public function afterGetInvoiceCollection(Order $subject, $result)
    {
        if (!$this->extend->isEnabled())
            return $result;

        foreach ($result->getItems() as $invoice) {
            $invoiceId = $invoice->getId();
            if ($invoiceId ) {
                $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                    $invoiceId,
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
    public function afterGetCreditmemosCollection(Order $subject, $result)
    {
        if (!$this->extend->isEnabled())
            return $result;

        foreach ($result->getItems() as $creditmemo) {
            $creditMemoId = $creditmemo->getId();
            if ($creditMemoId) {
                $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                    $creditMemoId,
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
        Order $subject,
        $result,
        $incrementId
    ) {
        if (!$this->extend->isEnabled())
            return $result;

        $orderId = $result->getId();
        if ($orderId) {
            $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                $orderId,
                ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID,
                $result
            );
        }

        return $result;
    }

    /**
     * Prevents reorder if order has product protection plans
     *
     * Needs to fire regardless of Extend being enabled or not
     *
     * @param Order $subject
     * @param bool $result
     * @return bool
     */
    public function afterCanReorder(Order $subject, bool $result)
    {
        if ($result) {
            $itemsCollection = $subject->getItemsCollection();
            /** @var Item $item */
            foreach ($itemsCollection->getItems() as $item) {
                $productName = $item->getName();

                if ($productName === Extend::WARRANTY_PRODUCT_NAME) {
                    return false;
                }
            }
        }

        return $result;
    }
}
