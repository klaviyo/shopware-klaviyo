<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers;

class GetExcludedSubscribersResponse
{
    private ExcludedSubscribersCollection $lists;
    private string $page;

    public function __construct(ExcludedSubscribersCollection $lists, string $page)
    {
        $this->lists = $lists;
        $this->page = $page;
    }

    public function getLists(): ExcludedSubscribersCollection
    {
        return $this->lists;
    }

    public function getPage(): string
    {
        return $this->page;
    }
}