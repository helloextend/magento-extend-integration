<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Order;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Model\Order\CreditmemoRepository;

class CreditmemoRepositoryPlugin
{
    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    /**
     * @var CreditmemoExtensionFactory
     */
    private \Magento\Sales\Api\Data\CreditmemoExtensionFactory $creditmemoExtensionFactory;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        \Magento\Sales\Api\Data\CreditmemoExtensionFactory $creditmemoExtensionFactory
    ){
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->creditmemoExtensionFactory = $creditmemoExtensionFactory;
    }

    /**
     * This plugin injects the Shipping Protection record into the credit memo's extension attributes if a matching record is found with a given credit memo ID
     *
     * @param CreditmemoRepository $subject
     * @param $result
     * @param $creditMemoId
     * @return mixed
     */
    public function afterGet(\Magento\Sales\Model\Order\CreditmemoRepository $subject, $result, $creditMemoId)
    {
        $shippingProtectionTotal = $this->shippingProtectionTotalRepository->get($creditMemoId, ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID);

        if (!$shippingProtectionTotal->getData() || sizeof($shippingProtectionTotal->getData()) === 0)
            return $result;

        $extensionAttributes = $result->getExtensionAttributes();
        $extensionAttributes->setShippingProtection(
            [
                'base' => $shippingProtectionTotal->getShippingProtectionBasePrice(),
                'base_currency' => $shippingProtectionTotal->getShippingProtectionBaseCurrency(),
                'price' => $shippingProtectionTotal->getShippingProtectionPrice(),
                'currency' => $shippingProtectionTotal->getShippingProtectionCurrency(),
                'sp_quote_id' => $shippingProtectionTotal->getSpQuoteId()
            ]
        );
        $result->setExtensionAttributes($extensionAttributes);

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
    public function afterSave(\Magento\Sales\Model\Order\CreditmemoRepository $subject, $result, $creditMemo)
    {
        $extensionAttributes = $creditMemo->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->creditmemoExtensionFactory->create();
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();
        $this->shippingProtectionTotalRepository->save($result->getEntityId(), ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID, $shippingProtection['sp_quote_id'], $shippingProtection['price'], $shippingProtection['currency'], $shippingProtection['base'], $shippingProtection['base_currency']);

        $resultExtensionAttributes = $result->getExtensionAttributes();
        $resultExtensionAttributes->setShippingProtection(
            [
                'base' => $shippingProtection['base'],
                'base_currency' => $shippingProtection['base_currency'],
                'price' => $shippingProtection['price'],
                'currency' => $shippingProtection['currency'],
                'sp_quote_id' => $shippingProtection['sp_quote_id']
            ]
        );
        $result->setExtensionAttributes($resultExtensionAttributes);

        return $result;
    }
}
