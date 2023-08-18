<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Quote\Item;

use Extend\Integration\Service\Extend;
use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class ToOrderItemPlugin
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;
    private Extend $extend;

    public function __construct(
        SerializerInterface $serializer,
        Extend $extend
    ) {
        $this->serializer = $serializer;
        $this->extend = $extend;
    }

    /**
     * After converting quote item to order item, migrate additional options to order item
     *
     * @param QuoteToOrderItem $subject
     * @param OrderItem $orderItem
     * @param AbstractItem $quoteItem
     * @param array $data
     * @return OrderItem
     */
    public function afterConvert(
        QuoteToOrderItem $subject,
        OrderItem $orderItem,
        AbstractItem $quoteItem,
        $data = []
    ) {
        if (!$this->extend->isEnabled())
            return $orderItem;

        $additionalOptions = $quoteItem->getOptionByCode('additional_options');
        if ($additionalOptions) {
            $options = $orderItem->getProductOptions();
            $options['additional_options'] = $this->serializer->unserialize(
                $additionalOptions->getValue()
            );
            $orderItem->setProductOptions($options);
        }

        return $orderItem;
    }
}
