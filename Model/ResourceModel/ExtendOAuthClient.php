<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\ResourceModel;

class ExtendOAuthClient extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const EXTEND_INTEGRATION_OAUTH_CLIENT = 'extend_integration_oauth_client';

    /**
     * ExtendOAuthClient resource constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            self::EXTEND_INTEGRATION_OAUTH_CLIENT,
            self::EXTEND_INTEGRATION_OAUTH_CLIENT . '_id'
        );
    }
}
