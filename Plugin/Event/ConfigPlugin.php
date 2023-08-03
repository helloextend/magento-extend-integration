<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Event;

use Extend\Integration\Service\Extend;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Config;

class ConfigPlugin
{
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Turns off observer calls if Extend Module turned off in admin
     *
     * @param Config $subject
     * @param $result
     * @return mixed
     */
    public function afterGetObservers(\Magento\Framework\Event\Config $subject, $result)
    {
        if ($result) {
            foreach ($result as $resultItemKey => $resultItem) {
                $thisClassPath = get_class($this);
                $thisExplodedClass = explode('\\', (string) $thisClassPath);
                if (isset($resultItem['instance'])) {
                    $resultNameExplodedClass = explode('\\', $resultItem['instance']);
                    if ($thisExplodedClass[0] == $resultNameExplodedClass[0] &&
                        $thisExplodedClass[1] == $resultNameExplodedClass[1] &&
                        $this->scopeConfig->getValue(Extend::ENABLE_EXTEND) === '0'
                    ) {
                        $resultItem['disabled'] = true;
                        $result[$resultItemKey] = $resultItem;
                    }
                }
            }
        }
        return $result;
    }
}
