<?php

namespace Klaviyo\Integration\Async;

class JobExecutionMessage
{
    private string $jobId;

    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }
}