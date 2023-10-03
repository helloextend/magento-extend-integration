<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class SaveOrderBeforeSalesModelQuote extends BaseExtendObserver
{
    /**
     * @var CartExtensionFactory
     */
    private $cartExtensionFactory;

    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @var Copy
     */
    private $objectCopyService;

    /**
     * @param LoggerInterface $logger
     * @param ExtendService $extendService
     * @param Integration $extendIntegrationService
     * @param StoreManagerInterface $storeManager
     * @param CartExtensionFactory $cartExtensionFactory
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param Copy $objectCopyService
     */
    public function __construct(
        LoggerInterface $logger,
        ExtendService $extendService,
        Integration $extendIntegrationService,
        StoreManagerInterface $storeManager,
        CartExtensionFactory $cartExtensionFactory,
        OrderExtensionFactory $orderExtensionFactory,
        Copy $objectCopyService
    ) {
        parent::__construct($logger, $extendService, $extendIntegrationService, $storeManager);
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->objectCopyService = $objectCopyService;
    }

    /**
     * Copy Shipping Protection extension attribute from quote to order
     *
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        $event = $observer->getEvent();

        /** @var Order $order */
        $order = $event->getData('order');

        /** @var Quote $quote */
        $quote = $event->getData('quote');

        $quoteExtensionAttributes = $quote->getExtensionAttributes();

        if ($quoteExtensionAttributes === null) {
            $quoteExtensionAttributes = $this->cartExtensionFactory->create();
        }

        if ($quoteExtensionAttributes->getShippingProtection() !== null) {
            $extensionAttributes = $order->getExtensionAttributes();

            if ($extensionAttributes === null) {
                $extensionAttributes = $this->orderExtensionFactory->create();
            }

            $order->setExtensionAttributes($extensionAttributes);

            $this->objectCopyService->copyFieldsetToTarget(
                'extend_integration_sales_convert_quote',
                'to_order',
                $quote,
                $order
            );
        }
    }
}
