<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionFactory;

class SaveOrderBeforeSalesModelQuote implements ObserverInterface
{
    /**
     * @var Copy
     */
    protected $objectCopyService;

    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;
    private CartExtensionFactory $cartExtensionFactory;

    /**
     * @param Copy $objectCopyService
     * @param OrderExtensionFactory $orderExtensionFactory
     */
    public function __construct(
        Copy $objectCopyService,
        OrderExtensionFactory $orderExtensionFactory,
        CartExtensionFactory $cartExtensionFactory
    ) {
        $this->objectCopyService = $objectCopyService;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->cartExtensionFactory = $cartExtensionFactory;
    }

    /**
     * Copy Shipping Protection extension attribute from quote to order
     *
     * @param Observer $observer
     * @return SaveOrderBeforeSalesModelQuote
     */
    public function execute(Observer $observer)
    {
        /* @var Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

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

        return $this;
    }
}
