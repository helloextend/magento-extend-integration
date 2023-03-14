<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\ResourceModel;

class ShippingProtectionTotal extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('extend_shipping_protection', 'extend_shipping_protection_id');
    }
}
