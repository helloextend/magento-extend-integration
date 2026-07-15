<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Mock;

use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * getStoreIds is a magic accessor on a category; declaring it as a real method lets it be mocked
 * via createMock on both PHPUnit 9 and 12 (addMethods is gone in 12).
 */
interface CategoryWithStoreIdsInterface extends CategoryInterface
{
    public function getStoreIds();
}
