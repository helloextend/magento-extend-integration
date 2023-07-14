<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Model\ProductProtection;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeSetInterface;

interface ProtectionPlanProductInterface
{
    /**
     * Create the protection plan product
     *
     * @param AttributeSetInterface $attributeSet
     * @return Magento\Catalog\Model\Product;
     */
    public function createProduct($attributeSet);
}
