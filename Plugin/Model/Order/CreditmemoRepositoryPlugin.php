<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Model\Order;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Extend\Integration\Service\Extend;
use Magento\Sales\Model\Order\CreditmemoRepository;

class CreditmemoRepositoryPlugin
{
    /**
     * @var OrderObserverHandler
     */
    private OrderObserverHandler $orderObserverHandler;
    private Extend $extend;

    public function __construct(
        OrderObserverHandler $orderObserverHandler,
        Extend $extend
    ) {
        $this->orderObserverHandler = $orderObserverHandler;
        $this->extend = $extend;
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
        if (!$this->extend->isEnabled())
            return $result;

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
