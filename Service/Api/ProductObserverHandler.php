<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Psr\log\LoggerInterface;

class ProductObserverHandler extends BaseObserverHandler
{

    public function __construct(
        LoggerInterface $logger,
        Integration $integration,
        StoreManagerInterface $storeManager,
        MetadataBuilder $metadataBuilder
    ) {
        parent::__construct(
            $logger,
            $integration,
            $storeManager,
            $metadataBuilder
        );
    }

    /**
     * @param array $integrationEndpoint
     * @param Product $product
     * @param array $additionalFields
     * @return void
     */
    public function execute(array $integrationEndpoint, Product $product, array $additionalFields)
    {
        try {
            $productId = $product->getId();
            $productSku = $product->getSku();
            $magentoStoreIds = $product->getStoreIds();
            $data = array_merge(['product_id' => $productId, 'product_sku' => $productSku], $additionalFields);

            [$headers, $body] = $this->metadataBuilder->execute($magentoStoreIds, $integrationEndpoint, $data);

            $this->integration->execute(
                $integrationEndpoint,
                $body,
                $headers
            );
        } catch (\Exception $exception) {
            // silently handle errors
            $this->logger->error('Extend Product Observer encountered the following error: ' . $exception->getMessage());
            $this->integration->logErrorToLoggingService($exception->getMessage(), $this->storeManager->getStore()->getId(), 'error');
        }
    }
}
