<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\BatchProductObserverHandler;
use Extend\Integration\Service\Extend as ExtendService;
use Magento\Framework\Event\Observer;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CatalogProductImportBunchSaveAfter extends BaseExtendObserver
{
    /**
     * @var BatchProductObserverHandler
     */
    private $batchProductObserverHandler;

    /**
     * @param LoggerInterface $logger
     * @param ExtendService $extendService
     * @param Integration $extendIntegrationService
     * @param StoreManagerInterface $storeManager
     * @param ProductObserverHandler $productObserverHandler
     */
    public function __construct(
       LoggerInterface $logger,
       ExtendService $extendService,
       Integration $extendIntegrationService,
       StoreManagerInterface $storeManager,
        BatchProductObserverHandler $batchProductObserverHandler
    ) {
        parent::__construct($logger, $extendService, $extendIntegrationService, $storeManager);
        $this->batchProductObserverHandler = $batchProductObserverHandler;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        $event = $observer->getEvent();

        $bunch = $event->getBunch();

        /** @var Product $adapter */
        $adapter = $event->getAdapter();

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
    }
}
