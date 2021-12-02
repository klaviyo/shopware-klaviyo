<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList;

class RemoveProfilesFromListResponse
{
    private bool $success;
    private string $errorDetails;

    public function __construct(bool $success, string $errorDetails = '')
    {
        $this->success = $success;
        $this->errorDetails = $errorDetails;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorDetails(): string
    {
        return $this->errorDetails;
    }
}