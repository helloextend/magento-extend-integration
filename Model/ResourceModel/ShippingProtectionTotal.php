<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\ResourceModel;

class ShippingProtectionTotal extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const EXTEND_SHIPPING_PROTECTION_TABLE = 'extend_shipping_protection';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            self::EXTEND_SHIPPING_PROTECTION_TABLE,
            self::EXTEND_SHIPPING_PROTECTION_TABLE . '_id'
        );
    }
}
