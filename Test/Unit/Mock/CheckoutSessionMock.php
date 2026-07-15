<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Mock;

use Magento\Checkout\Model\Session;

/**
 * hasData/setData/unsetData resolve through SessionManager::__call, so they are not
 * mockable by name on Session itself. Declaring them here lets a mock stub them with
 * onlyMethods while still satisfying the Session type hint that consumers require.
 */
class CheckoutSessionMock extends Session
{
    public function hasData($key = '')
    {
    }

    public function setData($key, $value = null)
    {
    }

    public function unsetData($key = null)
    {
    }
}
