<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Convert;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Model\ShippingProtectionTotalRepository;
use Extend\Integration\Model\ShippingProtectionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Framework\DataObject\Copy;
use Magento\Sales\Model\Convert\Order;

class OrderPlugin
{
    /**
     * @var InvoiceExtensionFactory
     */
    private InvoiceExtensionFactory $invoiceExtensionFactory;

    /**
     * @var Copy
     */
    private Copy $objectCopyService;

    /**
     * @var OrderExtensionFactory
     */
    private OrderExtensionFactory $orderExtensionFactory;

    /**
     * @var ShippingProtectionTotalRepository
     */
    private ShippingProtectionTotalRepository $shippingProtectionTotalRepository;
    private Http $http;
    private ShippingProtectionFactory $shippingProtectionFactory;

    /**
     * @param InvoiceExtensionFactory $invoiceExtensionFactory
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param Copy $objectCopyService
     * @param ShippingProtectionTotalRepository $shippingProtectionTotalRepository
     */
    public function __construct(
        InvoiceExtensionFactory $invoiceExtensionFactory,
        OrderExtensionFactory $orderExtensionFactory,
        Copy $objectCopyService,
        ShippingProtectionTotalRepository $shippingProtectionTotalRepository,
        Http $http,
        ShippingProtectionFactory $shippingProtectionFactory
    ) {
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->objectCopyService = $objectCopyService;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->http = $http;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
    }

    /**
     * This plugin injects the shipping protection record into the order's extension attributes, if a record is found with a matching order id
     *
     * @param Order $subject
     * @param $result
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function afterToInvoice(
        \Magento\Sales\Model\Convert\Order $subject,
        $result,
        \Magento\Sales\Model\Order $order
    ) {
        $orderExtensionAttributes = $order->getExtensionAttributes();
        if ($orderExtensionAttributes === null) {
            $orderExtensionAttributes = $this->orderExtensionFactory->create();
        }
        if ($orderExtensionAttributes->getShippingProtection() === null) {
            $shippingProtectionTotalData = $this->shippingProtectionTotalRepository->get(
                $order->getEntityId(),
                ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID
            );
            if ($shippingProtectionTotalData->getData()) {
                $shippingProtection = $this->shippingProtectionFactory->create();
                $shippingProtection->setBase(
                    $shippingProtectionTotalData->getShippingProtectionBasePrice()
                );
                $shippingProtection->setBaseCurrency(
                    $shippingProtectionTotalData->getShippingProtectionBaseCurrency()
                );
                $shippingProtection->setPrice(
                    $shippingProtectionTotalData->getShippingProtectionPrice()
                );
                $shippingProtection->setCurrency(
                    $shippingProtectionTotalData->getShippingProtectionCurrency()
                );
                $shippingProtection->setSpQuoteId($shippingProtectionTotalData->getSpQuoteId());
                $orderExtensionAttributes->setShippingProtection($shippingProtection);
            }
        }
        if ($orderExtensionAttributes->getShippingProtection() !== null) {
            $order->setExtensionAttributes($orderExtensionAttributes);

            $invoiceExtensionAttributes = $result->getExtensionAttributes();
            if ($invoiceExtensionAttributes === null) {
                $invoiceExtensionAttributes = $this->invoiceExtensionFactory->create();
            }
            $result->setExtensionAttributes($invoiceExtensionAttributes);

            $this->objectCopyService->copyFieldsetToTarget(
                'extend_integration_sales_convert_order',
                'to_invoice',
                $order,
                $result
            );
        }
        return $result;
    }

    /**
     * This plugin injects the shipping protection record into the order's extension attributes, if a record is found with a matching order id
     *
     * @param Order $subject
     * @param $result
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function afterToCreditmemo(
        \Magento\Sales\Model\Convert\Order $subject,
        $result,
        \Magento\Sales\Model\Order $order
    ) {
        $orderExtensionAttributes = $order->getExtensionAttributes();
        if ($orderExtensionAttributes === null) {
            $orderExtensionAttributes = $this->orderExtensionFactory->create();
        }
        if ($orderExtensionAttributes->getShippingProtection() === null) {
            $shippingProtectionTotalData = $this->shippingProtectionTotalRepository->get(
                $order->getEntityId(),
                ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID
            );
            if ($shippingProtectionTotalData->getData()) {
                $shippingProtection = $this->shippingProtectionFactory->create();
                $shippingProtection->setBase(
                    $shippingProtectionTotalData->getShippingProtectionBasePrice()
                );
                $shippingProtection->setBaseCurrency(
                    $shippingProtectionTotalData->getShippingProtectionBaseCurrency()
                );
                $shippingProtection->setPrice(
                    $shippingProtectionTotalData->getShippingProtectionPrice()
                );
                $shippingProtection->setCurrency(
                    $shippingProtectionTotalData->getShippingProtectionCurrency()
                );
                $shippingProtection->setSpQuoteId($shippingProtectionTotalData->getSpQuoteId());
                $orderExtensionAttributes->setShippingProtection($shippingProtection);
                $result->setData(
                    'original_shipping_protection',
                    $shippingProtectionTotalData->getShippingProtectionPrice()
                );
            }
        }
        if ($orderExtensionAttributes->getShippingProtection() !== null) {
            $order->setExtensionAttributes($orderExtensionAttributes);
            if ($post = $this->http->getPost('creditmemo')) {
                if (isset($post['shipping_protection'])) {
                    $creditMemoExtensionAttributes = $result->getExtensionAttributes();
                    if ($creditMemoExtensionAttributes === null) {
                        $creditMemoExtensionAttributes = $this->creditmemoExtensionFactory->create();
                    }
                    $shippingProtection = $this->shippingProtectionFactory->create();
                    $shippingProtection->setBase($post['shipping_protection']);
                    $shippingProtection->setBaseCurrency(
                        $shippingProtectionTotalData->getShippingProtectionBaseCurrency()
                    );
                    $shippingProtection->setPrice($post['shipping_protection']);
                    $shippingProtection->setCurrency(
                        $shippingProtectionTotalData->getShippingProtectionCurrency()
                    );
                    $shippingProtection->setSpQuoteId($shippingProtectionTotalData->getSpQuoteId());
                    $creditMemoExtensionAttributes->setShippingProtection($shippingProtection);
                    $result->setExtensionAttributes($creditMemoExtensionAttributes);

                    return $result;
                }
            }

            $creditMemoExtensionAttributes = $result->getExtensionAttributes();
            if ($creditMemoExtensionAttributes === null) {
                $creditMemoExtensionAttributes = $this->creditmemoExtensionFactory->create();
            }
            $result->setExtensionAttributes($creditMemoExtensionAttributes);

            $this->objectCopyService->copyFieldsetToTarget(
                'extend_integration_sales_convert_order',
                'to_cm',
                $order,
                $result
            );
        }
        return $result;
    }
}
