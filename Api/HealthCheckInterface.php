<?php
/*
 * Copyright Extend (c) 2024. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api;

use Extend\Integration\Api\Data\HealthCheckResponseInterface;

interface HealthCheckInterface
{
    /**
     * API route that proxies a request from the module's settings tab in the admin panel
     * to the Extend API to check the health of the integration
     * @return HealthCheckResponseInterface
     */
    public function check();
}
