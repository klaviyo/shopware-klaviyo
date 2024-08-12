<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleFullHistoricalSyncTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: ScheduleFullHistoricalSyncTask::class)]
final class ScheduleFullHistoricalSyncTaskHandler extends ScheduledTaskHandler
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
            $context = new Context(new SystemSource());
            $this->logger->notice("ScheduleFullHistoricalSyncTask started");
            $this->scheduleBackgroundJob->scheduleFullOrderSyncJob($context);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
