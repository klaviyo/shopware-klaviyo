<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common;

class EventTrackingResponse
{
    private bool $isSuccess;
    private ?string $errorDetail;

    public function __construct(bool $isSuccess, string $errorDetail = null)
    {
        $this->isSuccess = $isSuccess;
        $this->errorDetail = $errorDetail;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function getErrorDetails(): ?string
    {
        return $this->errorDetail;
    }
}
