<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Service\Api\Integration;
use Psr\log\LoggerInterface;

class CategoryObserverHandler extends BaseObserverHandler
{
    /**
     * @var StoreIntegrationRepositoryInterface
     */
    private $storeIntegrationRepository;

    /**
     * @param LoggerInterface $logger
     * @param Integration $integration
     * @param StoreManagerInterface $storeManager
     * @param MetadataBuilder $metadataBuilder
     * @param StoreIntegrationRepositoryInterface $storeIntegrationRepository
     */

    public function __construct(
        LoggerInterface $logger,
        Integration $integration,
        StoreManagerInterface $storeManager,
        MetadataBuilder $metadataBuilder,
        StoreIntegrationRepositoryInterface $storeIntegrationRepository
    ) {
        parent::__construct(
            $logger,
            $integration,
            $storeManager,
            $metadataBuilder
        );
        $this->storeIntegrationRepository = $storeIntegrationRepository;
    }

    /**
     * @param array $integrationEndpoint
     * @param CategoryInterface $product
     * @param array $additionalFields
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $integrationEndpoint, CategoryInterface $category, array $additionalFields)
    {
        try {
            $categoryId = $category->getId();
            if (!isset($categoryId)) {
                throw new LocalizedException(
                  new Phrase('The observed category is missing an id, which is required by the Extend Integration Service.')
                );
            }

            $categoryName = $category->getName();
            if (!isset($categoryName)) {
                throw new LocalizedException(
                  new Phrase('The observed category is missing a name, which is required by the Extend Integration Service.')
                );
            }

            $magentoStoreIds = $category->getStoreIds();

            $magentoStoresWithIntegration = [];

            foreach ($magentoStoreIds as $storeId) {
                try {
                    $storeIntegration = $this->storeIntegrationRepository->getByStoreIdAndActiveEnvironment($storeId);

                    if ($storeIntegration) {
                        $magentoStoresWithIntegration[] = $storeId;
                    }
                } catch (Exception $exception) {
                    // ignore if exception is a result of the store not being integrated
                    if ($exception instanceof NoSuchEntityException) {
                        $logMessage = 'No integration found for store with id: ' . $storeId;
                        $this->logger->warning($logMessage);
                        $this->integration->logErrorToLoggingService($logMessage, $this->storeManager->getStore()->getId(), 'warn');
                    } else {
                        throw $exception;
                    }
                }
            }

            // only send request if category is associated with at least one integrated store
            if (count($magentoStoresWithIntegration) > 0) {
                $data = array_merge(['category_id' => $categoryId, 'category_name' => $categoryName], $additionalFields);

                [$headers, $body] = $this->metadataBuilder->execute($magentoStoresWithIntegration, $integrationEndpoint, $data);

                $this->integration->execute(
                    $integrationEndpoint,
                    $body,
                    $headers
                );
            }
        } catch (Exception $exception) {
            // silently handle errors
            $this->logger->error('Extend Category Observer encountered the following error: ' . $exception->getMessage());
            $this->integration->logErrorToLoggingService($exception->getMessage(), $this->storeManager->getStore()->getId(), 'error');
        }
    }
}
