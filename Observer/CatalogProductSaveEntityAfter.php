<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\ProductObserverHandler;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Psr\Log\LoggerInterface;

class CatalogProductSaveEntityAfter extends BaseExtendObserver
{
    /**
     * @var ProductObserverHandler
     */
    private $productObserverHandler;

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
        ProductObserverHandler $productObserverHandler
    ) {
        parent::__construct($logger, $extendService, $extendIntegrationService, $storeManager);
        $this->productObserverHandler = $productObserverHandler;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        $endpoint = $this->resolveEndpoint($product);

        $this->productObserverHandler->execute($endpoint, $product, []);
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    private function resolveEndpoint(ProductInterface $product): array
    {
        if ($product->isObjectNew()) {
            return [
                'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_products_create'],
                'type' => 'middleware',
            ];
        }

        return [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_products_update'],
            'type' => 'middleware',
        ];
    }
}
