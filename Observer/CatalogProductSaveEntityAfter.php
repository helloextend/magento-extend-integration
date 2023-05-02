<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\ProductObserverHandler;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Psr\Log\LoggerInterface;

class CatalogProductSaveEntityAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductObserverHandler
     */
    private $productObserverHandler;

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
        ProductObserverHandler $productObserverHandler,
        Integration $integration,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->productObserverHandler = $productObserverHandler;
        $this->integration = $integration;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $endpoint = $this->resolveEndpoint($product);

        try {
            $this->productObserverHandler->execute($endpoint, $product, []);
        } catch (\Exception $exception) {
            // silently handle errors
            $this->logger->error(
                'Extend Product Observer Handler encountered the following error: ' .
                    $exception->getMessage()
            );
            $this->integration->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error'
            );
        }
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
