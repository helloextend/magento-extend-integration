<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Checkout\CustomerData;

use Magento\Sales\CustomerData\LastOrderedItems;
use Extend\Integration\Service\Extend;

class LastOrderedItemsPlugin
{
    /**
     * Prevents plans from being added to the cart outside of any of the Extend flows
     *
     * @param LastOrderedItems $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(LastOrderedItems $subject, array $result)
    {
        if (isset($result['items'])) {
            foreach ($result['items'] as $key => $item) {
              $productName = $item['name'];

              if ($productName === Extend::WARRANTY_PRODUCT_NAME) {
                  unset($result['items'][$key]);
              }
            }
        }

        $result['items'] = array_values($result['items']);

        return $result;
    }
}
