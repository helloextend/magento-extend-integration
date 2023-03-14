<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\ResourceModel;

class StoreIntegration extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('extend_store_integration', 'extend_store_integration_id');
    }

}
