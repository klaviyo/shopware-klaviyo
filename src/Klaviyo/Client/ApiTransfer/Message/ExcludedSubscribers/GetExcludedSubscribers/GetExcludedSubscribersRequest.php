<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;

class GetExcludedSubscribersRequest
{
    private ?string $count;
    private string $page;

    public function __construct(string $count, string $page)
    {
        $this->count = $count;
        $this->page = $page;
    }

    public function getCount(): ?string
    {
        return $this->count;
    }

    public function getPage(): string
    {
        return $this->page;
    }
}