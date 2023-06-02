<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\ResourceModel\StoreIntegration;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'extend_store_integration_id';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Extend\Integration\Model\StoreIntegration',
            'Extend\Integration\Model\ResourceModel\StoreIntegration'
        );
    }
}
