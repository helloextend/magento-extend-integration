<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Model\ProductProtection;

use Magento\Catalog\Model\Product;

interface ProtectionPlanProductInterface
{
    /**
     * Create the protection plan product
     *
     * @return Magento\Catalog\Model\Product;
     */
    public function createProduct();
}
