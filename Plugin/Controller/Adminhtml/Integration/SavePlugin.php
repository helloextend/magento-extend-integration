<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Controller\Adminhtml\Integration;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Model\ResourceModel\StoreIntegration;
use Extend\Integration\Model\ResourceModel\StoreIntegration\CollectionFactory;
use Extend\Integration\Service\Api\Integration as IntegrationService;
use Extend\Integration\Service\Api\MetadataBuilder;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Controller\Adminhtml\Integration;
use Magento\Integration\Controller\Adminhtml\Integration\Save;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

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
    private MetadataBuilder $metadataBuilder;
    private IntegrationService $integration;
    private OauthServiceInterface $oauthService;
    private IntegrationServiceInterface $integrationService;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    /**
     * @param StoreIntegrationRepositoryInterface $integrationStoresRepository
     * @param StoreIntegration $storeIntegrationResource
     * @param CollectionFactory $storeIntegrationCollection
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        StoreIntegrationRepositoryInterface $integrationStoresRepository,
        StoreIntegration $storeIntegrationResource,
        StoreIntegration\CollectionFactory $storeIntegrationCollection,
        ManagerInterface $messageManager,
        MetadataBuilder $metadataBuilder,
        IntegrationService $integration,
        OauthServiceInterface $oauthService,
        IntegrationServiceInterface $integrationService,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->integrationStoresRepository = $integrationStoresRepository;
        $this->storeIntegrationResource = $storeIntegrationResource;
        $this->storeIntegrationCollection = $storeIntegrationCollection;
        $this->messageManager = $messageManager;
        $this->metadataBuilder = $metadataBuilder;
        $this->integration = $integration;
        $this->oauthService = $oauthService;
        $this->integrationService = $integrationService;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
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
        $integration = $this->integrationService->get(
            $subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID)
        );
        if (isset($postData['integration_stores']) && (int) $integration->getSetupType() === 1) {
            $integrationStoresIds = (array) $postData['integration_stores'];
            $this->disableAllStoreAssociations(
                $subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID)
            );
            foreach ($integrationStoresIds as $integrationStoreId) {
                $this->integrationStoresRepository->saveStoreToIntegration(
                    $subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID),
                    $integrationStoreId
                );
                $this->sendIntegrationToExtend(
                    $subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID),
                    $integrationStoreId
                );
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
        $integration = $this->integrationService->get(
            $subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID)
        );
        if (isset($postData['integration_stores']) && (int) $integration->getSetupType() === 0) {
            $integrationStoreIds = $postData['integration_stores'];
            $this->disableAllStoreAssociations(
                $subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID)
            );
            foreach ($integrationStoreIds as $integrationStoreId) {
                $this->integrationStoresRepository->saveStoreToIntegration(
                    $subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID),
                    $integrationStoreId
                );
                $this->sendIntegrationToExtend(
                    $subject->getRequest()->getParam(Integration::PARAM_INTEGRATION_ID),
                    $integrationStoreId
                );
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
            ->addFieldToFilter(
                \Extend\Integration\Api\Data\StoreIntegrationInterface::INTEGRATION_ID,
                $integrationId
            )
            ->load();
        foreach ($storeIntegrations->getItems() as $storeIntegration) {
            $storeIntegration->setDisabled(1);
            $this->storeIntegrationResource->save($storeIntegration);
        }
    }

    private function sendIntegrationToExtend($integrationId, $storeId)
    {
        $integrationStore = $this->integrationStoresRepository->getByStoreIdAndIntegrationId(
            $storeId,
            $integrationId
        );
        $integration = $this->integrationService->get($integrationId);
        $oauth = $this->oauthService->loadConsumer($integration->getConsumerId());
        $store = $this->storeManager->getStore($storeId);

        $endpoint = [
            'path' => IntegrationService::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_create'],
            'type' => 'middleware',
        ];

        [$headers, $body] = $this->metadataBuilder->execute([], $endpoint, [
            'magentoStoreUuid' => $integrationStore->getStoreUuid(),
            'magentoStoreId' => $storeId,
            'magentoConsumerKey' => $oauth->getKey(),
            'storeDomain' => $this->scopeConfig->getValue(
                Store::XML_PATH_UNSECURE_BASE_URL,
                'store',
                $storeId
            ),
            'magentoApiSecretId' => $oauth->getSecret(),
            'name' => $store->getName(),
            'websiteId' => $store->getWebsiteId(),
            'weightUnit' => $this->scopeConfig->getValue(
                Data::XML_PATH_WEIGHT_UNIT,
                'store',
                $storeId
            ),
        ]);

        $this->integration->execute($endpoint, $body, $headers);
    }
}
