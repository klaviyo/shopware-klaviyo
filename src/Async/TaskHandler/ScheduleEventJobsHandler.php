<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleEventJobsTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class ScheduleEventJobsHandler extends ScheduledTaskHandler
{
    private ScheduleBackgroundJob $scheduleBackgroundJob;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
    }

    public function run(): void
    {
        $this->scheduleBackgroundJob->scheduleEventsProcessingJob();
    }

    public static function getHandledMessages(): iterable
    {
        return [ScheduleEventJobsTask::class];
    }
}
