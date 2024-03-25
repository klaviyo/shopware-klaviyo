<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleEventJobsTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: ScheduleEventJobsTask::class)]
final class ScheduleEventJobsHandler
{
    /**
     * @param ScheduleBackgroundJob $scheduleBackgroundJob
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ScheduleBackgroundJob $scheduleBackgroundJob,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param ScheduleEventJobsTask $task
     *
     * @return void
     */
    public function __invoke(ScheduleEventJobsTask $task): void
    {
        try {
            $this->scheduleBackgroundJob->scheduleEventsProcessingJob();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
