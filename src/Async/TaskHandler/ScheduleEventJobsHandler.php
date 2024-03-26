<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleEventJobsTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: ScheduleEventJobsTask::class)]
final class ScheduleEventJobsHandler extends ScheduledTaskHandler
{
    /**
     * @param EntityRepository $scheduledTaskRepository
     * @param ScheduleBackgroundJob $scheduleBackgroundJob
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected EntityRepository $scheduledTaskRepository,
        private readonly ScheduleBackgroundJob $scheduleBackgroundJob,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        try {
            $this->scheduleBackgroundJob->scheduleEventsProcessingJob();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
