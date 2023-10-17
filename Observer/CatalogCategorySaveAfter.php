<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\CategoryObserverHandler;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Psr\Log\LoggerInterface;

class CatalogCategorySaveAfter extends BaseExtendObserver
{
    /**
     * @var CategoryObserverHandler
     */
    private $categoryObserverHandler;

    /**
     * @param LoggerInterface $logger
     * @param ExtendService $extendService
     * @param Integration $extendIntegrationService
     * @param StoreManagerInterface $storeManager
     * @param CategoryObserverHandler $categoryObserverHandler
     */
    public function __construct(
        LoggerInterface $logger,
        ExtendService $extendService,
        Integration $extendIntegrationService,
        StoreManagerInterface $storeManager,
        CategoryObserverHandler $categoryObserverHandler
    ) {
        parent::__construct($logger, $extendService, $extendIntegrationService, $storeManager);
        $this->categoryObserverHandler = $categoryObserverHandler;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function _execute(Observer $observer)
    {
        $category = $observer->getEvent()->getCategory();

        if (isset($category)) {
            $endpoint = $this->resolveEndpoint($category);

            $this->categoryObserverHandler->execute($endpoint, $category, []);
        }
    }

    /**
     * @param CategoryInterface $category
     * @return array
     */
    private function resolveEndpoint(CategoryInterface $category): array
    {
        $categoryCreatedAt = $category->getCreatedAt();
        $categoryUpdatedAt = $category->getUpdatedAt();

        /**
         * We try to use the timestamps to determine if the category is being created or updated.
         * If the timestamps are the same, we assume the category is being created. If we don't have the data, i.e.
         * one or both timestamps is/are null, we default to create.
         * The category is upserted by the service anyway so we have some flexibility here.
         */
        if (!isset($categoryCreatedAt) || !isset($categoryUpdatedAt) || $categoryCreatedAt === $categoryUpdatedAt) {
            return [
                'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_categories_create'],
                'type' => 'middleware',
            ];
        }

        return [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_categories_update'],
            'type' => 'middleware',
        ];
    }
}
