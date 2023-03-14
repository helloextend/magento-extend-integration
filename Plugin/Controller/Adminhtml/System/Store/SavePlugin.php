<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Controller\Adminhtml\System\Store;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;

class SavePlugin
{

    private \Magento\Store\Api\StoreRepositoryInterface $storeRepository;
    private StoreIntegrationRepositoryInterface $integrationStoresRepository;

    public function __construct(
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        StoreIntegrationRepositoryInterface $integrationStoresRepository
    ) {
        $this->storeRepository = $storeRepository;
        $this->integrationStoresRepository = $integrationStoresRepository;
    }

    public function afterExecute(\Magento\Backend\Controller\Adminhtml\System\Store\Save $subject, $result)
    {
        if ($subject->getRequest()->isPost() && ($postData = $subject->getRequest()->getPostValue())) {
            if (
                empty($postData['store_type']) ||
                empty($postData['store_action']) ||
                $postData['store_type'] !== 'store' ||
                empty($postData['store']['code'])
            ) {
                return $result;
            }
            $store = $this->storeRepository->get($postData['store']['code']);
            $this->integrationStoresRepository->generateUuidForStore($store);
        }
        return $result;
    }
}
