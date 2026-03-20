<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class ExtendOrders extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/extend-orders.log';
}
