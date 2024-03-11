<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Identify;

class IdentifyProfileResponse
{
    private bool $success;
    private ?string $errorDetails;

    public function __construct(bool $success, string $errorDetails = null)
    {
        $this->success = $success;
        $this->errorDetails = $errorDetails;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorDetails(): ?string
    {
        return $this->errorDetails;
    }
}
