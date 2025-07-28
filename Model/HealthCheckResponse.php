<?php

namespace Extend\Integration\Model;

use Extend\Integration\Api\Data\HealthCheckResponseInterface;

class HealthCheckResponse implements HealthCheckResponseInterface
{
    /**
     * @var int
     */
    private int $code;

    /**
     * @var ?string
     */
    private ?string $message = null;

    /**
     * Get response code
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get response message
     *
     * @return ?string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set response code
     *
     * @param int $code
     * @return void
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * Set response message
     *
     * @param string $message
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
