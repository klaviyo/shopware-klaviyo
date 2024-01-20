<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\Common;

class ExcludedSubscribers
{
    private array $emails;

    public function __construct(array $emails)
    {
        $this->emails = $emails;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }
}
