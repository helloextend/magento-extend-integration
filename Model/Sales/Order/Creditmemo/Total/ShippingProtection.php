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
        // Check for credit memos which have already refunded shipping protection

        if ($shippingProtection = $creditmemo->getExtensionAttributes()->getShippingProtection()) {
            $shippingProtectionBasePrice = $shippingProtection->getBase();
            $shippingProtectionPrice = $shippingProtection->getPrice();

            $existingCreditMemoCount = 0;
            $existingCreditMemos = $creditmemo
                ->getOrder()
                ->getCreditmemosCollection()
                ->getItems();
            if ($existingCreditMemos) {
                foreach ($existingCreditMemos as $existingCreditMemo) {
                    if (
                        $shippingProtectionEntity = $this->shippingProtectionTotalRepository->get(
                            $existingCreditMemo->getId(),
                            \Extend\Integration\Api\Data\ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID
                        )
                    ) {
                        if (
                            $shippingProtectionEntity->getData() &&
                            $shippingProtectionEntity->getShippingProtectionPrice() > 0
                        ) {
                            $existingCreditMemoCount = 1;
                            break;
                        }
                    }
                }
            }

            if (
                count($creditmemo->getOrder()->getShipmentsCollection()) === 0 &&
                $existingCreditMemoCount === 0
            ) {
                $creditmemo->setBaseShippingProtection($shippingProtectionBasePrice);
                $creditmemo->setShippingProtection($shippingProtectionPrice);
                $creditmemo->setGrandTotal(
                    $creditmemo->getGrandTotal() + $creditmemo->getShippingProtection()
                );
                $creditmemo->setBaseGrandTotal(
                    $creditmemo->getBaseGrandTotal() + $creditmemo->getBaseShippingProtection()
                );
            } else {
                $this->zeroOutShippingProtection($creditmemo, $shippingProtection);
            }
        }

        return $this;
    }

    /**
     * If shipping protection cannot be refunded because it's already been shipped
     * then we need to zero it out in the totals and the extension attribute,
     * which will persist to the database.
     *
     * @param Creditmemo $creditmemo
     * @param ShippingProtectionInterface $shippingProtection
     * @return void
     */
    private function zeroOutShippingProtection(
        Creditmemo $creditmemo,
        ShippingProtectionInterface $shippingProtectionTotal
    ) {
        $creditmemo->setBaseShippingProtection(0.0);
        $creditmemo->setShippingProtection(0.0);

        $shippingProtection = $this->shippingProtectionFactory->create();

        $shippingProtection->setBase(0.0);
        $shippingProtection->setBaseCurrency($shippingProtectionTotal->getBaseCurrency());
        $shippingProtection->setPrice(0.0);
        $shippingProtection->setCurrency($shippingProtectionTotal->getCurrency());
        $shippingProtection->setSpQuoteId($shippingProtectionTotal->getSpQuoteId());

        $extensionAttributes = $creditmemo->getExtensionAttributes();
        $extensionAttributes->setShippingProtection($shippingProtection);
        $creditmemo->setExtensionAttributes($extensionAttributes);
        $creditmemo->setData('original_shipping_protection', 0);
    }
}
