<?php declare(strict_types=1);

namespace Klaviyo\Integration\Od\Scheduler\Async;

interface ParentAwareMessageInterface
{
    public function getParentJobId(): string;
}
