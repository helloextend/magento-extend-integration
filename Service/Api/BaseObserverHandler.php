<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Service\Api\Integration;
use Magento\Store\Model\StoreManagerInterface;
use Psr\log\LoggerInterface;

abstract class BaseObserverHandler
{
    protected LoggerInterface $logger;
    protected Integration $integration;
    protected StoreManagerInterface $storeManager;
    protected MetadataBuilder $metadataBuilder;

    protected function __construct(
        LoggerInterface $logger,
        Integration $integration,
        StoreManagerInterface $storeManager,
        MetadataBuilder $metadataBuilder
    ) {
        $this->logger = $logger;
        $this->integration = $integration;
        $this->storeManager = $storeManager;
        $this->metadataBuilder = $metadataBuilder;
    }
}
