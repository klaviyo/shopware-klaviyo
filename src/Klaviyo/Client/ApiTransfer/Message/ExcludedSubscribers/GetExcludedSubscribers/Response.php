<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;

class Response
{
    private array $emails;
    private ?string $nextPageUrl;

    public function __construct(
        array $emails,
        string $nextPageUrl = null
    ) {
        $this->emails = $emails;
        $this->nextPageUrl = $nextPageUrl;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getNextPageUrl(): ?string
    {
        return $this->nextPageUrl;
    }
}
