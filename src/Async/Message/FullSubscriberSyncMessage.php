<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

class FullSubscriberSyncMessage
{
    private string $jobId;

    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }
}
