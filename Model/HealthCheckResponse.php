<?php

namespace Extend\Integration\Model;

use Extend\Integration\Api\Data\HealthCheckResponseInterface;

class HealthCheckResponse implements HealthCheckResponseInterface
{
    private int $code;
    private ?string $message = null;

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getMessage(): string|null
    {
        return $this->message;
    }

    /**
     * @param int $code
     * @return void
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @param string $message
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
