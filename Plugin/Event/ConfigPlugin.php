<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Event;

use Extend\Integration\Service\Extend;

class ConfigPlugin
{
    public function afterGetObservers(\Magento\Framework\Event\Config $subject, $result)
    {
        if ($result) {
            foreach ($result as $resultItem) {
                $thisClassPath = get_class($this);
                $thisExplodedClass = explode('\\', (string) $thisClassPath);
                $resultNameExplodedClass = explode('\\', $resultItem['name']);
                if (
                    $thisExplodedClass[0] == $resultNameExplodedClass[0] &&
                    $thisExplodedClass[1] == $thisExplodedClass[1] &&
                    $this->scopeConfig->getValue(Extend::ENABLE_EXTEND) === 0
                ) {
                    $resultItem['disabled'] = 1;
                }
            }
        }
        return $result;
    }
}
