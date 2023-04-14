<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api;

interface ProductProtectionInterface {
  public function add(int $entityId, string $ppPlanId, float $price, int $term, string $title, string $coverageType, string $token): void;
}