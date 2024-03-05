<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common;

class EventTrackingResponse
{
    private bool $isSuccess;
    private ?string $detail;

    public function __construct(bool $isSuccess, string $detail = null)
    {
        $this->isSuccess = $isSuccess;
        $this->detail = $detail;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function getDetail(string $detail = null): ?string
    {
        return $this->detail;
    }
}
