<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class AdditionalConfigVars implements \Magento\Checkout\Model\ConfigProviderInterface
{
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $config['extendEnable'] = $this->scopeConfig->getValue(
            \Extend\Integration\Service\Extend::ENABLE_SHIPPING_PROTECTION,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getCode()
        );
        return $config;
    }
}
