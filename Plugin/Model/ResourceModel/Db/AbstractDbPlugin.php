<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\ResourceModel\Db;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;

class AbstractDbPlugin
{
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;
    private CartExtensionFactory $cartExtensionFactory;
    private OrderExtensionFactory $orderExtensionFactory;
    private InvoiceExtensionFactory $invoiceExtensionFactory;
    private CreditmemoExtensionFactory $creditmemoExtensionFactory;

    public function __construct(
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        CartExtensionFactory $cartExtensionFactory,
        OrderExtensionFactory $orderExtensionFactory,
        InvoiceExtensionFactory $invoiceExtensionFactory,
        CreditmemoExtensionFactory $creditmemoExtensionFactory
    ) {
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->creditmemoExtensionFactory = $creditmemoExtensionFactory;
    }

    /**
     * Because of the various ways that Magento loads orders, invoices, and credit memos,
     * we get wider coverage loading our extension attribute by plugging into the AbstractDb::load method,
     * and we rule out many edge cases caused by 3rd party modules.
     *
     * @param AbstractDb $subject
     * @param $result
     * @param AbstractModel $object
     * @param $value
     * @param $field
     * @return mixed
     */
    public function afterLoad(
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $subject,
        $result,
        \Magento\Framework\Model\AbstractModel $object,
        $value,
        $field = null
    ) {
        if (!$object instanceof \Magento\Quote\Model\Quote &&
            !$object instanceof \Magento\Sales\Model\Order &&
            !$object instanceof \Magento\Sales\Model\Order\Invoice &&
            !$object instanceof \Magento\Sales\Model\Order\Creditmemo
        ) {
            return $result;
        }

        $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
            $value,
            $this->getSPEntityTypeId($object),
            $object
        );

        return $result;
    }

    /**
     * Because of the various ways that Magento saves quotes, orders, invoices, and credit memos,
     * we get wider coverage saving our extension attribute by plugging into the AbstractDb::save method,
     * and we rule out many edge cases caused by 3rd party modules.
     *
     * @param AbstractDb $subject
     * @param $result
     * @param AbstractModel $object
     * @return mixed
     */
    public function afterSave(
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $subject,
        $result,
        \Magento\Framework\Model\AbstractModel $object
    ) {
        if (!$object instanceof \Magento\Quote\Model\Quote &&
            !$object instanceof \Magento\Sales\Model\Order &&
            !$object instanceof \Magento\Sales\Model\Order\Invoice &&
            !$object instanceof \Magento\Sales\Model\Order\Creditmemo
        ) {
            return $result;
        }

        $extensionAttributes = $object->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->getExtensionFactoryClass($object);
        }
        $shippingProtection = $extensionAttributes->getShippingProtection();

        if ($result && $shippingProtection) {
            $this->shippingProtectionTotalRepository->saveAndResaturateExtensionAttribute(
                $shippingProtection,
                $object,
                $this->getSPEntityTypeId($object)
            );
        }
        return $result;
    }

    /**
     * This grabs the appropriate extension factory class based on the object type.
     *
     * @param $object
     * @return void
     */
    private function getExtensionFactoryClass($object)
    {
        switch (true) {
            case $object instanceof \Magento\Quote\Model\Quote:
                return $this->cartExtensionFactory->create();
                break;
            case $object instanceof \Magento\Sales\Model\Order:
                return $this->orderExtensionFactory->create();
                break;
            case $object instanceof \Magento\Sales\Model\Order\Invoice:
                return $this->invoiceExtensionFactory->create();
                break;
            case $object instanceof \Magento\Sales\Model\Order\Creditmemo:
                return $this->creditmemoExtensionFactory->create();
                break;
        }
    }

    /**
     * This grabs the appropriate SP entity type id based on the object type.
     *
     * @param $object
     * @return int|void
     */
    private function getSPEntityTypeId($object)
    {
        switch (true) {
            case $object instanceof \Magento\Quote\Model\Quote:
                return ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID;
                break;
            case $object instanceof \Magento\Sales\Model\Order:
                return ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID;
                break;
            case $object instanceof \Magento\Sales\Model\Order\Invoice:
                return ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID;
                break;
            case $object instanceof \Magento\Sales\Model\Order\Creditmemo:
                return ShippingProtectionTotalInterface::CREDITMEMO_ENTITY_TYPE_ID;
                break;
        }
    }
}
