<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ProductMetadataInterface;
use Extend\Integration\Service\Api\AccessTokenBuilder;

class MetadataBuilder
{
    private IdentityService $identityService;
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private ProductMetadataInterface $productMetadata;
    private AccessTokenBuilder $accessTokenBuilder;

    public function __construct(
        IdentityService $identityService,
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        ProductMetadataInterface $productMetadata,
        AccessTokenBuilder $accessTokenBuilder
    ) {
        $this->identityService = $identityService;
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->productMetadata = $productMetadata;
        $this->accessTokenBuilder = $accessTokenBuilder;
    }

    /**
     * @param int[] $storeIds
     * @param array $integrationEndpoint
     * @param array $data
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute($storeIds, $integrationEndpoint, $data): array
    {
        $headers = [];
        $body = [];

        $headers['X-Extend-Access-Token'] = $this->accessTokenBuilder->getAccessToken();
        $headers['Content-Type'] = 'application/json';
        $fullMagentoVersion = $this->productMetadata->getVersion();
        $trimmedMagentoVersion = strstr($fullMagentoVersion, '-', true);
        $headers['X-Magento-Version'] = !$trimmedMagentoVersion ? $fullMagentoVersion : $trimmedMagentoVersion;

        $body['webhook_id'] = $this->identityService->generateId();
        $body['webhook_created_at'] = time();
        $body['topic'] = str_replace('/webhooks/', '', $integrationEndpoint['path']);
        $body['data'] = $data;

        foreach ($storeIds as $storeId) {
            $body['magento_store_uuids'][] =
                $this->storeIntegrationRepository
                ->getByStoreIdAndActiveEnvironment($storeId)
                ->getStoreUuid();
        }

        return [$headers, $body];
    }
}
