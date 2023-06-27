<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;

class InvoiceSaveAfter implements ObserverInterface
{
    /**
     * @var \Psr\log\LoggerInterface
     */
    private $logger;
    private OrderObserverHandler $orderObserverHandler;
    private Integration $integration;
    private StoreManagerInterface $storeManager;

    public function __construct(
        \Psr\log\LoggerInterface $logger,
        OrderObserverHandler $orderObserverHandler,
        Integration $integration,
        StoreManagerInterface $storeManager,
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        InvoiceExtensionFactory $invoiceExtensionFactory
    ) {
        $this->logger = $logger;
        $this->orderObserverHandler = $orderObserverHandler;
        $this->integration = $integration;
        $this->storeManager = $storeManager;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $extensionAttributes = $observer->getInvoice()->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->invoiceExtensionFactory->create();
            }
            $shippingProtection = $extensionAttributes->getShippingProtection();

            if ($observer->getInvoice() && $shippingProtection) {
                $this->shippingProtectionTotalRepository->saveAndResaturateExtensionAttribute(
                    $shippingProtection,
                    $observer->getInvoice(),
                    ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID
                );
            }
            $this->orderObserverHandler->execute(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_create'],
                    'type' => 'middleware',
                ],
                $observer->getInvoice()->getOrder(),
                ['invoice_id' => $observer->getInvoice()->getId()]
            );
        } catch (\Exception $exception) {
            // silently handle errors
            $this->logger->error(
                'Extend Order Observer Handler encountered the following error: ' .
                    $exception->getMessage()
            );
            $this->integration->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error'
            );
        }
    }
}
