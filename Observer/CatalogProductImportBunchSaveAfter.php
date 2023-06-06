<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\BatchProductObserverHandler;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CatalogProductImportBunchSaveAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BatchProductObserverHandler
     */
    private $batchProductObserverHandler;

    /**
     * @var Integration
     */
    private $integration;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        LoggerInterface $logger,
        BatchProductObserverHandler $batchProductObserverHandler,
        Integration $integration,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->batchProductObserverHandler = $batchProductObserverHandler;
        $this->integration = $integration;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $bunch = $observer->getEvent()->getBunch();

            /** @var Product $adapter */
            $adapter = $observer->getEvent()->getAdapter();

            $productIds = [];

            foreach ($bunch as $rowNum => $rowData) {
                $productData = $adapter->getNewSku($rowData[Product::COL_SKU]);

                if (isset($productData['entity_id'])) {
                    $productId = $productData['entity_id'];

                    array_push($productIds, $productId);
                }
            }

            $endpoint = [
                'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_products_create'],
                'type' => 'middleware',
            ];

            $this->batchProductObserverHandler->execute($endpoint, $productIds, []);
        } catch (\Exception $exception) {
            // silently handle errors
            $this->logger->error(
                'Extend Batch Product Observer Handler encountered the following error: ' .
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
