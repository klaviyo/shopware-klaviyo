<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job;

interface JobHandlerInterface
{
    public function execute(object $message): JobResult;
}
