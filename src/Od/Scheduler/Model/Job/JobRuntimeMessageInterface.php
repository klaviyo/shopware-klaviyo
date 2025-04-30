<?php declare(strict_types=1);

namespace Klaviyo\Integration\Od\Scheduler\Model\Job;

interface JobRuntimeMessageInterface
{
    public function getType(): string;

    public function getMessage(): string;
}