<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api\Data;

interface HealthCheckResponseInterface
{
    /**
     * Set response code from Extend server's healthcheck response, or 500 if an error occurred
     * @param int $code
     * @return void
     */
    public function setCode(int $code): void;

    /**
     * Set response message from Extend server's healthcheck response or error message
     * @param string $message
     * @return void
     */
    public function setMessage(string $message): void;

    /**
     * Get response code from Extend server's healthcheck response, or 500 if an error occurred
     * @return int
     */
    public function getCode(): int;

    /**
     * Get response message from Extend server's healthcheck response or error message
     * @return string|null
     */
    public function getMessage(): ?string;
}
