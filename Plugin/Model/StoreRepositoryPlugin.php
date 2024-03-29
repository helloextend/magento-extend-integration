<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Service\Extend;
use Magento\Store\Api\Data\StoreExtensionFactory;
use Magento\Store\Model\StoreRepository;

class StoreRepositoryPlugin
{
    /**
     * @var StoreExtensionFactory
     */
    private StoreExtensionFactory $storeExtensionFactory;
    private StoreIntegrationRepositoryInterface $integrationStoresRepository;
    private Extend $extend;

    /**
     * @param StoreExtensionFactory $storeExtensionFactory
     */
    public function __construct(
        StoreExtensionFactory $storeExtensionFactory,
        StoreIntegrationRepositoryInterface $integrationStoresRepository,
        Extend $extend
    ) {
        $this->storeExtensionFactory = $storeExtensionFactory;
        $this->integrationStoresRepository = $integrationStoresRepository;
        $this->extend = $extend;
    }

    /**
     * This plugin injects the UUIDs into an Integrations Store record for the SDK, when a store code is given
     *
     * @param StoreRepository $subject
     * @param $result
     * @return mixed
     */
    public function afterGet(\Magento\Store\Model\StoreRepository $subject, $result, $code)
    {
        if (!$this->extend->isEnabled())
            return $result;

        $integrationStores = $this->integrationStoresRepository->get($code);

        if (!$integrationStores->getData() || sizeof($integrationStores->getData()) === 0) {
            return $result;
        }

        $extensionAttributes = $result->getExtensionAttributes();
        $extensionAttributes->setUuid([
            'magento' => $integrationStores->getStoreUuid(),
            'extend' => $integrationStores->getExtendStoreUuid(),
        ]);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }

    /**
     * This plugin injects the UUIDs into an Integrations Store record for the SDK when a store ID is given
     *
     * @param StoreRepository $subject
     * @param $result
     * @param $storeId
     * @return mixed
     */
    public function afterGetById(\Magento\Store\Model\StoreRepository $subject, $result, $storeId)
    {
        if (!$this->extend->isEnabled())
            return $result;

        $integrationStores = $this->integrationStoresRepository->getById($storeId);

        if (!$integrationStores->getData() || sizeof($integrationStores->getData()) === 0) {
            return $result;
        }

        $extensionAttributes = $result->getExtensionAttributes();
        $extensionAttributes->setUuid([
            'magento' => $integrationStores->getStoreUuid(),
            'extend' => $integrationStores->getExtendStoreUuid(),
        ]);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }
}
