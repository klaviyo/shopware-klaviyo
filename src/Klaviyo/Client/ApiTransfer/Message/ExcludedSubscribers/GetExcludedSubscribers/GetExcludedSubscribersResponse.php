<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;

class GetExcludedSubscribersResponse
{
    private array $emails;
    private string $page;
    private string $totalEmailsCount;

    public function __construct(
        array $emails,
        string $page,
        string $totalEmailsCount
    ) {
        $this->emails = $emails;
        $this->page = $page;
        $this->totalEmailsCount = $totalEmailsCount;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function getTotalEmailsCount(): string
    {
        return $this->totalEmailsCount;
    }
}