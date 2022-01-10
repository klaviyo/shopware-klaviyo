<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleEventJobsTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class ScheduleEventJobsHandler extends ScheduledTaskHandler
{
    private EntityRepositoryInterface $eventRepository;
    private ScheduleBackgroundJob $scheduleBackgroundJob;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        EntityRepositoryInterface $eventRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->eventRepository = $eventRepository;
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(1000);
        $iterator = new RepositoryIterator($this->eventRepository, $context, $criteria);

        while (($eventIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleOrderEventsSyncJob($context, $eventIds);
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [ScheduleEventJobsTask::class];
    }
}
