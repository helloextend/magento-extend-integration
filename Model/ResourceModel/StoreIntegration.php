<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\ResourceModel;

class StoreIntegration extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const EXTEND_STORE_INTEGRATION_TABLE = 'extend_store_integration';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            self::EXTEND_STORE_INTEGRATION_TABLE,
            self::EXTEND_STORE_INTEGRATION_TABLE . '_id'
        );
    }
}
