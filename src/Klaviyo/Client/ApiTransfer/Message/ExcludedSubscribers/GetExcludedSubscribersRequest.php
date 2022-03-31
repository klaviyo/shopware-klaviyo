<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers;

class GetExcludedSubscribersRequest
{
    private ?string $page;

    public function __construct(string $page)
    {
        $this->page = $page;
    }

    public function getPage(): string
    {
        return $this->page;
    }
}