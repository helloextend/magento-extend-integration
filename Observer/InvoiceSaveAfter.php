<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Registry;

class InvoiceSaveAfter extends BaseExtendObserver
{
    /**
     * @var InvoiceExtensionFactory
     */
    private $invoiceExtensionFactory;

    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private $shippingProtectionTotalRepository;

    /**
     * @var OrderObserverHandler
     */
    private $orderObserverHandler;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @param LoggerInterface $logger
     * @param ExtendService $extendService
     * @param Integration $extendIntegrationService
     * @param StoreManagerInterface $storeManager
     * @param InvoiceExtensionFactory $invoiceExtensionFactory
     * @param ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository
     * @param OrderObserverHandler $orderObserverHandler
     * @param Registry $registry
     */
    public function __construct(
        LoggerInterface $logger,
        ExtendService $extendService,
        Integration $extendIntegrationService,
        StoreManagerInterface $storeManager,
        InvoiceExtensionFactory $invoiceExtensionFactory,
        ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
        OrderObserverHandler $orderObserverHandler,
        Registry $registry
    ) {
        parent::__construct($logger, $extendService, $extendIntegrationService, $storeManager);
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
        $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
        $this->orderObserverHandler = $orderObserverHandler;
        $this->registry = $registry;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        // Flag that other observers can reference to avoid making a duplicate webhook call
        $this->registry->register('extend.invoice.created', true);

        $invoice = $observer->getInvoice();

        $extensionAttributes = $invoice->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->invoiceExtensionFactory->create();
        }

        $shippingProtection = $extensionAttributes->getShippingProtection();

        if ($invoice && !$invoice->getOmitSp() && $shippingProtection) {
            $this->shippingProtectionTotalRepository->saveAndResaturateExtensionAttribute(
                $shippingProtection,
                $invoice,
                ShippingProtectionTotalInterface::INVOICE_ENTITY_TYPE_ID
            );
        }

        $this->orderObserverHandler->execute(
            [
                'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_create'],
                'type' => 'middleware',
            ],
            $invoice->getOrder(),
            ['invoice_id' => $invoice->getId()]
        );
    }
}
