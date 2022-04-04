<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\Common\ExcludedSubscribersCollection;

class GetExcludedSubscribersResponse
{
    private ExcludedSubscribersCollection $lists;
    private string $page;
    private string $totalEmailsValue;

    public function __construct(
        ExcludedSubscribersCollection $lists,
        string $page,
        string $totalEmailsValue
    ) {
        $this->lists = $lists;
        $this->page = $page;
        $this->totalEmailsValue = $totalEmailsValue;
    }

    public function getLists(): ExcludedSubscribersCollection
    {
        return $this->lists;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function getTotalEmailsValue(): string
    {
        return $this->totalEmailsValue;
    }
}