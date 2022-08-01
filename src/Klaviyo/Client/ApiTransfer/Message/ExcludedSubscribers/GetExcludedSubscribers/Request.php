<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;

class Request
{
    private ?int $count;
    private int $page;

    public function __construct(int $count, int $page)
    {
        $this->count = $count;
        $this->page = $page;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
