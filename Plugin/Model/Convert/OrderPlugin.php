<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Convert;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Model\ShippingProtectionTotalRepository;
use Extend\Integration\Model\ShippingProtectionFactory;
use Extend\Integration\Service\Extend;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Framework\DataObject\Copy;
use Magento\Sales\Model\Convert\Order;
use Psr\Log\LoggerInterface;

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
    private CreditmemoExtensionFactory $creditmemoExtensionFactory;
    private Extend $extend;
    private LoggerInterface $logger;

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
        ShippingProtectionFactory $shippingProtectionFactory,
        CreditmemoExtensionFactory $creditmemoExtensionFactory,
        Extend $extend,
        LoggerInterface $logger
    ) {
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->objectCopyService = $objectCopyService;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->http = $http;
        $this->shippingProtectionFactory = $shippingProtectionFactory;
        $this->creditmemoExtensionFactory = $creditmemoExtensionFactory;
        $this->extend = $extend;
        $this->logger = $logger;
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
        if (!$this->extend->isEnabled())
            return $result;

        $orderExtensionAttributes = $order->getExtensionAttributes();
        if ($orderExtensionAttributes === null) {
            $orderExtensionAttributes = $this->orderExtensionFactory->create();
        }
        if ($orderExtensionAttributes->getShippingProtection() === null) {
            $shippingProtectionTotalData = $this->getShippingProtectionTotalData($order);

            if ($shippingProtectionTotalData && $shippingProtectionTotalData->getData()) {
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
                $shippingProtection->setShippingProtectionTax($shippingProtectionTotalData->getShippingProtectionTax());
                $shippingProtection->setOfferType($shippingProtectionTotalData->getOfferType());
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
        if (!$this->extend->isEnabled())
            return $result;

        $orderExtensionAttributes = $order->getExtensionAttributes();
        if ($orderExtensionAttributes === null) {
            $orderExtensionAttributes = $this->orderExtensionFactory->create();
        }
        if ($orderExtensionAttributes->getShippingProtection() === null) {
            $shippingProtectionTotalData = $this->getShippingProtectionTotalData($order);

            if ($shippingProtectionTotalData && $shippingProtectionTotalData->getData()) {
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
                $shippingProtection->setShippingProtectionTax($shippingProtectionTotalData->getShippingProtectionTax());
                $shippingProtection->setOfferType($shippingProtectionTotalData->getOfferType());
                $orderExtensionAttributes->setShippingProtection($shippingProtection);
            }
        }
        if ($orderExtensionAttributes->getShippingProtection() !== null) {
            $order->setExtensionAttributes($orderExtensionAttributes);
            $shippingProtectionTotalData = $orderExtensionAttributes->getShippingProtection();
            $result->setData(
              'original_shipping_protection',
              $shippingProtectionTotalData['base']
            );
            if ($post = $this->http->getPost('creditmemo')) {
                if (isset($post['shipping_protection'])) {
                    $postShippingProtectionPrice = $post['shipping_protection'];

                    // If SPG and the value is empty, mark SP as removed from credit memo and set price to 0
                    // The value is set to empty for the UI to display the input field as empty
                    if ($shippingProtectionTotalData->getOfferType() === ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE && $postShippingProtectionPrice === '') {
                        $postShippingProtectionPrice = 0;
                        $result->setSpgSpRemovedFromCreditMemo(true);
                    }

                    $creditMemoExtensionAttributes = $result->getExtensionAttributes();
                    if ($creditMemoExtensionAttributes === null) {
                        $creditMemoExtensionAttributes = $this->creditmemoExtensionFactory->create();
                    }
                    $shippingProtection = $this->shippingProtectionFactory->create();
                    $shippingProtection->setBase($postShippingProtectionPrice);
                    $shippingProtection->setBaseCurrency(
                        $shippingProtectionTotalData['base_currency']
                    );
                    $shippingProtection->setPrice($postShippingProtectionPrice);
                    $shippingProtection->setCurrency($shippingProtectionTotalData['currency']);
                    $shippingProtection->setSpQuoteId($shippingProtectionTotalData->getSpQuoteId());
                    $shippingProtection->setShippingProtectionTax($shippingProtectionTotalData->getShippingProtectionTax());
                    $shippingProtection->setOfferType($shippingProtectionTotalData->getOfferType());
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

    /**
     * Get shipping protection total data for an order
     *
     * Attempts to retrieve shipping protection data first by ORDER entity type (if order is persisted),
     * then falls back to QUOTE entity type to handle "Authorize and Capture" payment flows where
     * invoice/creditmemo creation happens before order shipping protection data is saved.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return \Extend\Integration\Model\ShippingProtectionTotal|null
     */
    private function getShippingProtectionTotalData(\Magento\Sales\Model\Order $order)
    {
        $shippingProtectionTotalData = null;

        // try to retrieve an SP total data using the ORDER entity type first (available if order is persisted)
        if ($order->getEntityId() !== null) {
          try {
            $shippingProtectionTotalData = $this->shippingProtectionTotalRepository->get(
                $order->getEntityId(),
                ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID
            );
          } catch (\Exception $e) {
            // Log the error and continue with fallback lookup below
            $this->logger->error('Error retrieving shipping protection total data for order with entity id ' . $order->getEntityId() . ': ' . $e->getMessage());
          }
        }

        // fall back to lookup based on the QUOTE entity type, if order is not found or not persisted yet.
        // this handles "Authorize and Capture" payment action flows where conversion to invoice happens before the
        // order is persisted and shipping protection data is saved to it.
        if ((!$shippingProtectionTotalData || !$shippingProtectionTotalData->getData()) && $order->getQuoteId()) {
          try {
            $shippingProtectionTotalData = $this->shippingProtectionTotalRepository->get(
                $order->getQuoteId(),
                ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID
            );
          } catch (\Exception $e) {
            // Log the error and continue returning null so as to not break the flow
            $this->logger->error('Error retrieving shipping protection total data for order with quote id ' . $order->getQuoteId() . ': ' . $e->getMessage());
          }
        }

        return $shippingProtectionTotalData;
    }
}
