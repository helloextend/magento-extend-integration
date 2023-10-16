<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\ResourceModel\ExtendOAuthClient;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'extend_integration_oauth_client_id';

    /**
     * ExtendOAuthClient collection constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Extend\Integration\Model\ExtendOAuthClient::class,
            \Extend\Integration\Model\ResourceModel\ExtendOAuthClient::class
        );
    }
}
