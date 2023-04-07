<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Preference\Framework\Http;

class ZendClientPreference extends \Magento\Framework\HTTP\ZendClient
{
    protected function _trySetCurlAdapter()
    {
        if (str_contains($this->getUri()->getHost(), 'extend.com')) {
            $this->setAdapter('Zend_Http_Client_Adapter_Curl');
        } else {
            if (extension_loaded('curl')) {
                $this->setAdapter(new \Magento\Framework\HTTP\Adapter\Curl());
            }
        }
        return $this;
    }
}
