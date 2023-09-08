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
use Extend\Integration\Service\Extend;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Oauth\Exception as OauthException;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Controller\Adminhtml\Integration;
use Magento\Integration\Controller\Adminhtml\Integration\Save;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\log\LoggerInterface;
use Exception;

class SavePlugin
{
    /**
     * @var StoreIntegrationRepositoryInterface
     */
    private $integrationStoresRepository;

    /**
     * @var StoreIntegration
     */
    private $storeIntegrationResource;

    /**
     * @var CollectionFactory
     */
    private $storeIntegrationCollection;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var MetadataBuilder
     */
    private $metadataBuilder;

    /**
     * @var IntegrationService
     */
    private $integration;

    /**
     * @var OauthServiceInterface
     */
    private $oauthService;

    /**
     * @var IntegrationServiceInterface
     */
    private $integrationService;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Extend
     */
    private $extend;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param StoreIntegrationRepositoryInterface $integrationStoresRepository
     * @param StoreIntegration $storeIntegrationResource
     * @param CollectionFactory $storeIntegrationCollection
     * @param ManagerInterface $messageManager
     * @param MetadataBuilder $metadataBuilder
     * @param IntegrationService $integration
     * @param OauthServiceInterface $oauthService
     * @param IntegrationServiceInterface $integrationService
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Extend $extend
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreIntegrationRepositoryInterface $integrationStoresRepository,
        StoreIntegration $storeIntegrationResource,
        CollectionFactory $storeIntegrationCollection,
        ManagerInterface $messageManager,
        MetadataBuilder $metadataBuilder,
        IntegrationService $integration,
        OauthServiceInterface $oauthService,
        IntegrationServiceInterface $integrationService,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Extend $extend,
        LoggerInterface $logger
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
        $this->extend = $extend;
        $this->logger = $logger;
    }

    /**
     * Save stores to integration, using the Extend custom table
     *
     * @param Save $subject
     * @param callable $proceed
     * @return void
     * @throws AlreadyExistsException
     */
    public function aroundExecute(Save $subject, callable $proceed)
    {
        try {
            if (!$this->extend->isEnabled()) {
              return $proceed();
            }

            $request = $subject->getRequest();

            $integrationId = $request->getParam(Integration::PARAM_INTEGRATION_ID);

            if ($integrationId) {
                $postData = $request->getPostValue();

                $integration = $this->integrationService->get(
                  $integrationId
                );

                if (isset($postData['integration_stores'])) {
                    $this->disableAllStoreAssociations($integrationId);

                    $integrationStoresIds = (array) $postData['integration_stores'];

                    $integrationIsActive = $integration->getStatus() !== 0;

                    foreach ($integrationStoresIds as $integrationStoreId) {
                        $this->integrationStoresRepository->saveStoreToIntegration(
                            $integrationId,
                            $integrationStoreId
                        );

                        if ($integrationIsActive) {
                            $this->sendIntegrationToExtend(
                                $integrationId,
                                $integrationStoreId
                            );
                        }
                    }

                    $this->messageManager->addSuccessMessage(
                        __('Your selected stores were saved to the Extend Integration.')
                    );

                    if ((int) $integration->getSetupType() === 0) {
                        $proceed();
                    } else {
                        $subject->getResponse()->setRedirect($subject->getUrl('*/*/'));
                    }
                } elseif ((int) $integration->getSetupType() === 1) {
                    $this->messageManager->addSuccessMessage(
                        __('No additional stores were saved to the Extend Integration.')
                    );

                    $subject->getResponse()->setRedirect($subject->getUrl('*/*/'));
                } else {
                    $proceed();
                }
            } else {
                $proceed();
            }
        } catch (Exception $exception) {
            $this->logger->error('An error occurred while saving an integration: ' . $exception->getMessage());
            $proceed();
        }
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

    /**
     * Send stores to Extend Magento Service when added to an integration after it's already been activated.
     *
     * @param $integrationId
     * @param $storeId
     * @return void
     * @throws IntegrationException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws OauthException
     */
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
            'path' => IntegrationService::EXTEND_INTEGRATION_ENDPOINTS['webhooks_stores_create'],
            'type' => 'middleware',
        ];

        $oauthKey = $oauth->getKey();

        if ($oauthKey && $oauth->getSecret()) {
            [$headers, $body] = $this->metadataBuilder->execute([], $endpoint, [
                'magentoStoreUuid' => $integrationStore->getStoreUuid(),
                'magentoStoreId' => $storeId,
                'magentoConsumerKey' => $oauthKey,
                'extendStoreId' => $integrationStore->getExtendStoreUuid(),
                'storeDomain' => rtrim(
                    str_replace(
                        ['https://', 'http://'],
                        '',
                        $this->scopeConfig->getValue(
                            Store::XML_PATH_UNSECURE_BASE_URL,
                            'store',
                            $storeId
                        )
                    ),
                    '/'
                ),
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
}
