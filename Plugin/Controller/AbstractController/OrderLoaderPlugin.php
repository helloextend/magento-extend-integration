<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Controller\AbstractController;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Model\ShippingProtectionFactory;
use Extend\Integration\Service\Extend;
use Magento\Framework\Registry;
use Magento\Sales\Controller\AbstractController\OrderLoader;

class OrderLoaderPlugin
{
    /**
     * @var ShippingProtectionTotalRepositoryInterface
     */
    private ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var Extend
     */
    private Extend $extend;

    public function __construct(
          ShippingProtectionTotalRepositoryInterface $shippingProtectionTotalRepository,
          Registry $registry,
          Extend $extend
      ) {
          $this->shippingProtectionTotalRepository = $shippingProtectionTotalRepository;
          $this->registry = $registry;
          $this->extend = $extend;
    }

      /**
       * @param OrderLoader $subject
       * @param $result
       * @param $request
       * @return mixed
       */
        public function afterLoad(
            \Magento\Sales\Controller\AbstractController\OrderLoader $subject,
            $result,
            $request
        ) {
            if (!$this->extend->isEnabled())
                return $result;

            $orderId = (int) $request->getParam('order_id');

            if (!$orderId) {
                return $result;
            }

            $this->shippingProtectionTotalRepository->getAndSaturateExtensionAttributes(
                $orderId,
                ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID,
                $this->registry->registry('current_order')
            );

            return $result;
        }
}
