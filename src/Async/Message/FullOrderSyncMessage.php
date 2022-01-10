<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

class FullOrderSyncMessage
{
    private string $jobId;

    /**
     * @var string[]
     */
    private array $salesChannelIds;

    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function setSalesChannelIds(array $salesChannelIds = []): void
    {
        $this->salesChannelIds = $salesChannelIds;
    }

    public function getSalesChannelIds(): array
    {
        return $this->salesChannelIds;
    }
}
