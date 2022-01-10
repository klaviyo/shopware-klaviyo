<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

class SubscriberSyncMessage
{
    private string $jobId;
    private string $salesChannelId;
    private array $subscriberIds = [];

    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSubscriberIds(array $subscriberIds): void
    {
        $this->subscriberIds = $subscriberIds;
    }

    public function getSubscriberIds(): array
    {
        return $this->subscriberIds;
    }
}
