<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service;

class Extend
{
    /**
     * warranty product sku
     */
    public const WARRANTY_PRODUCT_SKU = 'extend-protection-plan';

    /**
     * warranty product name
     */
    public const WARRANTY_PRODUCT_NAME = 'Extend Protection Plan';

    /**
     * warranty product sku
     */
    public const WARRANTY_PRODUCT_ATTRIBUTE_SET_NAME = 'Extend Products';

    /**
     * This const allows for future scalability in markets with stricter privacy laws.
     */
    public const COLLECT_ALL_ORDERS = true;

    public const SHIPPING_PROTECTION_LABEL = 'Shipping Protection';

    public const ENABLE_PRODUCT_PROTECTION = 'extend_plans/product_protection/enable';

    public const ENABLE_SHIPPING_PROTECTION = 'extend_plans/shipping_protection/enable';

    public const ENABLE_EXTEND = 'extend/integration/enable';

    /**
     * Lead token url param
     */
    public const LEAD_TOKEN_URL_PARAM = 'leadToken';
}
