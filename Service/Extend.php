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

    public const ENABLE_CART_BALANCING = 'extend_plans/product_protection/enable_cart_balancing';

    public const ENABLE_PRODUCT_PROTECTION_CART_OFFER = 'extend_plans/product_protection/offer_display_settings/enable_cart_offer';
    public const ENABLE_PRODUCT_PROTECTION_MINICART_OFFER = 'extend_plans/product_protection/offer_display_settings/enable_minicart_offer';
    public const ENABLE_PRODUCT_PROTECTION_PRODUCT_DISPLAY_PAGE_OFFER = 'extend_plans/product_protection/offer_display_settings/enable_pdp_offer';
    public const ENABLE_PRODUCT_PROTECTION_POST_PURCHASE_LEAD_MODAL_OFFER = 'extend_plans/product_protection/offer_display_settings/enable_post_purchase_lead_modal_offer';
    public const ENABLE_PRODUCT_PROTECTION_PRODUCT_CATALOG_PAGE_MODAL_OFFER = 'extend_plans/product_protection/offer_display_settings/enable_product_catalog_page_modal_offer';

    /**
     * Lead token url param
     */
    public const LEAD_TOKEN_URL_PARAM = 'leadToken';
}
