<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers;

class GetExcludedSubscribersResponse
{
    private ExcludedSubscribersCollection $lists;
    private int $page;

    public function __construct(ExcludedSubscribersCollection $lists, int $page)
    {
        $this->lists = $lists;
        $this->page = $page;
    }

    public function getLists(): ExcludedSubscribersCollection
    {
        return $this->lists;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}