<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class AdditionalConfigVars implements \Magento\Checkout\Model\ConfigProviderInterface
{
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $config['extendEnable'] = $this->scopeConfig->getValue(
            \Extend\Integration\Service\Extend::ENABLE_SHIPPING_PROTECTION
        );
        return $config;
    }
}
