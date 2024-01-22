<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;

class Request
{
    private ?int $count;
    private ?string $nextPageUrl;

    public function __construct(int $count, string $nextPageUrl = null)
    {
        $this->count = $count;
        $this->nextPageUrl = $nextPageUrl;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getNextPageUrl(): ?string
    {
        return $this->nextPageUrl;
    }

    public function setNextPageUrl(string $nextPageUrl): Request
    {
        $this->nextPageUrl = $nextPageUrl;

        return $this;
    }
}
