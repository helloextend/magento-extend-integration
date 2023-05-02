<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Psr\log\LoggerInterface;
use Exception;

class BatchProductObserverHandler extends BaseObserverHandler
{
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        LoggerInterface $logger,
        Integration $integration,
        StoreManagerInterface $storeManager,
        MetadataBuilder $metadataBuilder,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($logger, $integration, $storeManager, $metadataBuilder);
        $this->productRepository = $productRepository;
    }

    /**
     * @param array $integrationEndpoint
     * @param array $productIds
     * @param array $additionalFields
     * @return void
     */
    public function execute($integrationEndpoint, $productIds, $additionalFields)
    {
        try {
            [$headers, $body] = $this->metadataBuilder->execute([], $integrationEndpoint, []);

            foreach ($productIds as $productId) {
                /** @var Product $product */
                $product = $this->productRepository->getById($productId);
                $magentoStoreIds = $product->getStoreIds();

                [$currentHeaders, $currentBody] = $this->metadataBuilder->execute(
                    $magentoStoreIds,
                    $integrationEndpoint,
                    array_merge($additionalFields, [
                        'product_id' => $productId,
                    ])
                );

                $body['magento_store_uuids'] = $currentBody['magento_store_uuids'];
                $body['data'] = $currentBody['data'];

                $this->integration->execute($integrationEndpoint, $body, $headers);
            }
        } catch (Exception $exception) {
            // silently handle errors
            $this->logger->error(
                'Extend Batch Product Observer encountered the following error: ' .
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
