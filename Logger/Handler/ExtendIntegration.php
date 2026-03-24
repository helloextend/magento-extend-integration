<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class ExtendIntegration extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/extend/integration.log';
}
