<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Quote\Item;

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

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function afterConvert(
        QuoteToOrderItem $subject,
        OrderItem $orderItem,
        AbstractItem $quoteItem,
        $data = []
    ) {
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
