<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Checkout\CustomerData;

use Extend\Integration\Plugin\Checkout\CustomerData\LastOrderedItemsPlugin;
use Extend\Integration\Service\Extend;
use Magento\Sales\CustomerData\LastOrderedItems;
use PHPUnit\Framework\TestCase;

class LastOrderedItemsPluginTest extends TestCase
{
    /**
     * @var LastOrderedItems|MockObject
     */
    private $subject;

    /**
     * @var array
     */
    private $regularProductOrderItem = [
      'name' => 'Regular Product',
    ];

    /**
     * @var array
     */
    private $warrantyProductOrderItem = [
      'name' => Extend::WARRANTY_PRODUCT_NAME
    ];

    /**
     * @var array
     */
    private $result;

    /**
     * @var LastOrderedItemsPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->subject = $this->createMock(LastOrderedItems::class);
        $this->result = [
          'items' => [
            $this->regularProductOrderItem,
            $this->warrantyProductOrderItem
          ]
        ];
        $this->plugin = new LastOrderedItemsPlugin();
    }

    public function testAfterGetSectionDataFiltersOutExtendPlans()
    {
        $this->assertEquals(
          [
            'items' => [
              $this->regularProductOrderItem
            ]
          ],
          $this->plugin->afterGetSectionData($this->subject, $this->result)
        );
    }
}
