<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api;

interface ProductProtectionInterface {
    /**
   * Add product protection to cart
   *
   * @param int $quantity
   * @param string $productId
   * @param string $planId
   * @param int $price
   * @param int $term
   * @param string $coverageType
   * @param string $leadToken = null
   * @param float $listPrice = null
   * @param string $orderOfferPlanId = null
   * @return void
   */
  public function add(int $quantity, string $productId, string $planId, int $price, int $term, string $coverageType, string $leadToken = null, float $listPrice = null, string $orderOfferPlanId = null): void;
}