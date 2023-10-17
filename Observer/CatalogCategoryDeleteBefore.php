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
use Psr\Log\LoggerInterface;

class CatalogCategoryDeleteBefore extends BaseExtendObserver
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
          $endpoint = [
            'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_categories_delete'],
            'type' => 'middleware',
        ];

        $this->categoryObserverHandler->execute($endpoint, $category, []);
      }
    }
}
