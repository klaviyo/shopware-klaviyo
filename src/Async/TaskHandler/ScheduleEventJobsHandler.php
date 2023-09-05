<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleEventJobsTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Psr\Log\LoggerInterface;

class ScheduleEventJobsHandler extends ScheduledTaskHandler
{
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->logger = $logger;
    }

    public function run(): void
    {
        try {
            $this->scheduleBackgroundJob->scheduleEventsProcessingJob();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [ScheduleEventJobsTask::class];
    }
}
