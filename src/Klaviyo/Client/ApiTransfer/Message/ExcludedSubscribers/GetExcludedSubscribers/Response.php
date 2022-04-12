<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;

class Response
{
    private array $emails;
    private int $page;
    private int $totalEmailsCount;

    public function __construct(
        array $emails,
        int $page,
        int $totalEmailsCount
    ) {
        $this->emails = $emails;
        $this->page = $page;
        $this->totalEmailsCount = $totalEmailsCount;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getTotalEmailsCount(): int
    {
        return $this->totalEmailsCount;
    }
}
