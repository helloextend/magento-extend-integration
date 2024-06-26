<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Order;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Model\Order\CreditmemoRepository;
use Extend\Integration\Model\ShippingProtectionFactory;
use Extend\Integration\Service\Extend;

class CreditmemoRepositoryPlugin
{
    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    /**
     * @var CreditmemoExtensionFactory
     */
    private CreditmemoExtensionFactory $creditmemoExtensionFactory;
    private ShippingProtectionFactory $shippingProtectionFactory;
    private OrderObserverHandler $orderObserverHandler;

    private Extend $extend;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        CreditmemoExtensionFactory $creditmemoExtensionFactory,
        ShippingProtectionFactory $shippingProtectionFactory,
        OrderObserverHandler $orderObserverHandler,
        Extend $extend
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->creditmemoExtensionFactory = $creditmemoExtensionFactory;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
        $this->orderObserverHandler = $orderObserverHandler;
        $this->extend = $extend;
    }

    /**
     * This plugin injects the Shipping Protection record into the credit memo's extension attributes if a matching record is found with a given credit memo ID
     *
     * @param CreditmemoRepository $subject
     * @param $result
     * @param $creditMemoId
     * @return mixed
     */
    public function afterGet(
        CreditmemoRepository $subject,
        $result,
        $creditMemoId
    ) {
        if (!$this->extend->isEnabled()) {
            return $result;
        }

        $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
            $creditMemoId,
            ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID,
            $result
        );

        return $result;
    }

    /**
     * This save the Shipping Protection data from the credit memo's extension attributes into the Shipping Protection totals table, saving the entity type and credit memo ID as well
     *
     * @param CreditmemoRepository $subject
     * @param $result
     * @param $creditMemo
     * @return mixed
     */
    public function afterSave(
        CreditmemoRepository $subject,
        $result,
        $creditMemo
    ) {
        if (!$this->extend->isEnabled()) {
            return $result;
        }

        $extensionAttributes = $creditMemo->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->creditmemoExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        $isRefunding = $shippingProtection && $shippingProtection->getPrice() > 0;

        // SPG SP will be refunded unless it was marked as excluded from the credit memo by an admin
        $isRefundingSpg = $shippingProtection && $shippingProtection->getOfferType() === ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE && !$creditMemo->getSpgSpRemovedFromCreditMemo();

        if (!$creditMemo->getOmitSp() && $result && $shippingProtection && ($isRefunding || $isRefundingSpg)) {
            $this->shippingProtectionTotalRepository->saveAndResaturateExtensionAttribute(
                $shippingProtection,
                $result,
                ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID
            );
        }
        $this->orderObserverHandler->execute(
            [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_cancel'],
            'type' => 'middleware',
            ],
            $result->getOrder(),
            ['credit_memo_id' => $creditMemo->getId()]
        );
        return $result;
    }
}
