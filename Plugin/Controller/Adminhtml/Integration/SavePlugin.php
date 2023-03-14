<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Controller\Adminhtml\Integration;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Model\ResourceModel\StoreIntegration;
use Extend\Integration\Model\ResourceModel\StoreIntegration\CollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Integration\Controller\Adminhtml\Integration;
use Magento\Integration\Controller\Adminhtml\Integration\Save;

class SavePlugin
{
    /**
     * @var StoreIntegrationRepositoryInterface
     */
    private StoreIntegrationRepositoryInterface $integrationStoresRepository;

    /**
     * @var StoreIntegration
     */
    private StoreIntegration $storeIntegrationResource;

    /**
     * @var CollectionFactory
     */
    private StoreIntegration\CollectionFactory $storeIntegrationCollection;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @param StoreIntegrationRepositoryInterface $integrationStoresRepository
     * @param StoreIntegration $storeIntegrationResource
     * @param CollectionFactory $storeIntegrationCollection
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        StoreIntegrationRepositoryInterface $integrationStoresRepository,
        StoreIntegration                    $storeIntegrationResource,
        StoreIntegration\CollectionFactory  $storeIntegrationCollection,
        ManagerInterface                    $messageManager
    ) {
        $this->integrationStoresRepository = $integrationStoresRepository;
        $this->storeIntegrationResource = $storeIntegrationResource;
        $this->storeIntegrationCollection = $storeIntegrationCollection;
        $this->messageManager = $messageManager;
    }

    /**
     * Save stores to integration, using the Extend custom table,
     * when an integration was preinstalled with the Extend Integration module
     *
     * @param Save $subject
     * @param callable $proceed
     * @return void
     * @throws AlreadyExistsException
     */
    public function aroundExecute(Save $subject, callable $proceed)
    {
        $postData = $subject->getRequest()->getPostValue();
        if (isset($postData['integration_stores'])) {
            $integrationStores = $postData['integration_stores'];
            $this->disableAllStoreAssociations($subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID));
            foreach ($integrationStores as $integrationStore) {
                $this->integrationStoresRepository->saveStoreToIntegration($subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID), $integrationStore);
            }
            $this->messageManager->addSuccessMessage(
                __('Your selected stores were saved to the Extend Integration.')
                );
            $subject->getResponse()->setRedirect($subject->getUrl('*/*/'));
        } else {
            $proceed();
        }
    }

    /**
     * Save stores to integration, using the Extend custom table,
     * when an integration was created in the admin panel
     *
     * @param Save $subject
     * @param $result
     * @return mixed
     * @throws AlreadyExistsException
     */
    public function afterExecute(Save $subject, $result)
    {
        $postData = $subject->getRequest()->getPostValue();
        if (isset($postData['integration_stores'])) {
            $integrationStores = $postData['integration_stores'];
            $this->disableAllStoreAssociations($subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID));
            foreach ($integrationStores as $integrationStore) {
                $this->integrationStoresRepository->saveStoreToIntegration($subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID), $integrationStore);
            }
        }

        return $result;
    }

    /**
     * Disables all stores and re-enables the stores that were selected,
     * or remained selected, since we're dealing with a multi-select dropdown.
     *
     * @param $integrationId
     * @return void
     * @throws AlreadyExistsException
     */
    private function disableAllStoreAssociations($integrationId)
    {
        $storeIntegrationCollection = $this->storeIntegrationCollection->create();
        $storeIntegrations = $storeIntegrationCollection
            ->addFieldToFilter(\Extend\Integration\Api\Data\StoreIntegrationInterface::INTEGRATION_ID, $integrationId)
            ->load();
        foreach ($storeIntegrations->getItems() as $storeIntegration) {
            $storeIntegration->setDisabled(1);
            $this->storeIntegrationResource->save($storeIntegration);
        }
    }
}
