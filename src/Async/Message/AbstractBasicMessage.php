<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Od\Scheduler\Async\JobMessageInterface;

abstract class AbstractBasicMessage implements JobMessageInterface
{
    private string $jobId;
    protected string $name;
    protected static string $defaultName = 'Unnamed Operation';

    public function __construct(
        string $jobId,
        ?string $name = null
    ) {
        $this->jobId = $jobId;
        $this->name = $name ?? static::$defaultName;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function getJobName(): string
    {
        return $this->name;
    }
}
