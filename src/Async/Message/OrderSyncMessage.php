<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

class OrderSyncMessage
{
    private string $jobId;
    private string $salesChannelId;
    private array $orderIds = [];

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

    public function setOrderIds(array $orderIds): void
    {
        $this->orderIds = $orderIds;
    }

    public function getOrderIds(): array
    {
        return $this->orderIds;
    }
}
