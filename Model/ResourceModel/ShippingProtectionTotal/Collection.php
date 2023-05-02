<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\ResourceModel\ShippingProtectionTotal;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'extend_shipping_protection_id';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Extend\Integration\Model\ShippingProtectionTotal', 'Extend\Integration\Model\ResourceModel\ShippingProtectionTotal');
    }
}
