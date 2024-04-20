<?php

declare(strict_types=1);

namespace Klaviyo\Integration\System\Scheduling\ExcludedSubscriberSync;

class Result
{
    /**
     * @var array<string, string[]>
     */
    private array $emails = [];
    private array $subscriberIds = [];

    /**
     * @var \Throwable[]
     */
    private array $errors = [];

    public function addEmails(string $channelId, array $emails): void
    {
        $this->emails[$channelId] = \array_merge($this->emails[$channelId] ?? [], $emails);
    }

    public function addSubscriberIds(string $channelId, array $subscriberIds): void
    {
        $this->subscriberIds[$channelId] = \array_merge($this->subscriberIds[$channelId] ?? [], $subscriberIds);
    }

    /**
     * @return array<string, string[]>
     */
    public function all(): array
    {
        return $this->emails;
    }

    public function getAllSubscribersIds(): array
    {
        return $this->subscriberIds;
    }

    public function addError(\Throwable $e): void
    {
        $this->errors[] = $e;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
