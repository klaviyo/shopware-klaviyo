<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;

class ExcludedSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-excluded-subscriber-sync-handler';
    private const EXCLUDED_SUBSCRIBER_BATCH_SIZE = 1000;

    public function execute(object $message): JobResult
    {
        // search in newsletter recipient and change statuses
    }
}