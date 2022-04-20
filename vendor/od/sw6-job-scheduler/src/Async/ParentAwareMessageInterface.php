<?php declare(strict_types=1);

namespace Od\Scheduler\Async;

interface ParentAwareMessageInterface
{
    public function getParentJobId(): string;
}
