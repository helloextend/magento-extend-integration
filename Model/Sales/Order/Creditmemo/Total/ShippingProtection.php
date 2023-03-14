<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Sales\Order\Creditmemo\Total;

use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;

class ShippingProtection extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
    ){
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
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
            $shippingProtectionBasePrice = $shippingProtection['base'];
            $shippingProtectionPrice = $shippingProtection['price'];

            $existingCreditMemoCount = 0;
            $existingCreditMemos = $creditmemo->getOrder()->getCreditmemosCollection()->getItems();
            if ($existingCreditMemos) {
                foreach ($existingCreditMemos as $existingCreditMemo) {
                    if (
                        $shippingProtectionEntity = $this->shippingProtectionTotalRepository->get(
                            $existingCreditMemo->getId(),
                            \Extend\Integration\Api\Data\ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID
                        )
                    ) {
                        if ($shippingProtectionEntity->getShippingProtectionPrice() > 0) {
                            $existingCreditMemoCount = 1;
                            break;
                        }
                    }
                }
            }

            if (count($creditmemo->getOrder()->getShipmentsCollection()) === 0 && $existingCreditMemoCount === 0) {
                $creditmemo->setBaseShippingProtection($shippingProtectionBasePrice);
                $creditmemo->setShippingProtection($shippingProtectionPrice);
                $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getShippingProtection());
                $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBaseShippingProtection());
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
     * @param array $shippingProtection
     * @return void
     */
    private function zeroOutShippingProtection(Creditmemo $creditmemo, array $shippingProtection)
    {
        $creditmemo->setBaseShippingProtection(0.00);
        $creditmemo->setShippingProtection(0.00);
        $shippingProtection = [
            'base' => 0.00,
            'base_currency' => $shippingProtection['base_currency'],
            'price' => 0.00,
            'currency' => $shippingProtection['currency'],
            'sp_quote_id' => $shippingProtection['sp_quote_id']
        ];
        $extensionAttributes = $creditmemo->getExtensionAttributes();
        $extensionAttributes->setShippingProtection($shippingProtection);
        $creditmemo->setExtensionAttributes($extensionAttributes);
        $creditmemo->setData('original_shipping_protection', 0);
    }
}
