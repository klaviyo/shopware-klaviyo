<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

class OrderEventSyncMessage
{
    private string $jobId;
    private array $eventIds;

    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function setEventIds(array $eventIds): void
    {
        $this->eventIds = $eventIds;
    }

    public function getEventIds(): array
    {
        return $this->eventIds;
    }
}
