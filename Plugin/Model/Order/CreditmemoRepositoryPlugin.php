<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Order;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Magento\Sales\Model\Order\CreditmemoRepository;

class CreditmemoRepositoryPlugin
{
    /**
     * @var OrderObserverHandler
     */
    private OrderObserverHandler $orderObserverHandler;

    public function __construct(OrderObserverHandler $orderObserverHandler)
    {
        $this->orderObserverHandler = $orderObserverHandler;
    }

    /**
     * This calls the Extend API endpoint to cancel the full or partial order at Extend.
     *
     * @param CreditmemoRepository $subject
     * @param $result
     * @param $creditMemo
     * @return mixed
     */
    public function afterSave(
        \Magento\Sales\Model\Order\CreditmemoRepository $subject,
        $result,
        $creditMemo
    ) {
        $this->orderObserverHandler->execute(
            [
                'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_cancel'],
                'type' => 'middleware',
            ],
            $result->getOrder(),
            ['credit_memo_id' => $creditMemo->getId()]
        );

        return $result;
    }
}
