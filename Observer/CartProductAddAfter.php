<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Observer;
 
use Extend\Integration\Service\Extend;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
 
class CartProductAddAfter implements ObserverInterface
{
  public function execute(\Magento\Framework\Event\Observer $observer) {
      $item = $observer->getEvent()->getData('quote_item');         
      $item = ( $item->getParentItem() ? $item->getParentItem() : $item );
      if($item->getProduct()->getSku() == Extend::WARRANTY_PRODUCT_SKU) {
          $this->setPPPrice($item);
      }
  }

  private function setPPPrice($item) {
    $price = $item->getProduct()->getData('extend_plan_price');
    $item->setCustomPrice($price);
    $item->setOriginalCustomPrice($price);
    $item->getProduct()->setIsSuperMode(true);
  }

}