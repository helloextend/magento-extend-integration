<?php

namespace Extend\Integration\Cron;

use Extend\Integration\Service\Api\AccessTokenBuilder;
use Extend\Integration\Service\Extend;

class ExtendAccessToken
{
  /**
   * @var AccessTokenBuilder
   */
  private AccessTokenBuilder $accessTokenBuilder;

  /**
   * @var Extend
   */
  private Extend $extend;

  public function __construct(
    AccessTokenBuilder $accessTokenBuilder,
    Extend $extend
  ) {
    $this->accessTokenBuilder = $accessTokenBuilder;
    $this->extend = $extend;
  }

  /**
   * Execute the cron job
   *
   * @return void
   */
  public function execute()
  {
    if (!$this->extend->isEnabled()) {
      return;
    }

    // Generate a new access token if current expiry is within 15 minutes
    // (the cron job runs every 5 minutes)
    $this->accessTokenBuilder->getAccessToken(60 * 15); // secs x mins
  }
}
