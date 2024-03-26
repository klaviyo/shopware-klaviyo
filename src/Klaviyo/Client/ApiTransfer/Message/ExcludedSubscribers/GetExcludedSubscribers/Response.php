<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;

class Response
{
    private array $emails;
    private ?string $nextPageUrl;
    private ?bool $isSuccess;
    private ?string $errorDetails;

    public function __construct(
        array $emails,
        string $nextPageUrl = null,
        bool $isSuccess = null,
        string $errorDetails = null
    ) {
        $this->emails = $emails;
        $this->nextPageUrl = $nextPageUrl;
        $this->isSuccess = $isSuccess;
        $this->errorDetails = $errorDetails;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getNextPageUrl(): ?string
    {
        return $this->nextPageUrl;
    }

    public function isSuccess(): ?bool
    {
        return $this->isSuccess;
    }

    public function getErrorDetails(): ?string
    {
        return $this->errorDetails;
    }
}
