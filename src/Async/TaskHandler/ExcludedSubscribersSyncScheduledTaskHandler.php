<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ExcludedSubscribersSyncScheduledTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ExcludedSubscribersSyncScheduledTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        protected EntityRepository $scheduledTaskRepository,
        private readonly ScheduleBackgroundJob $scheduleBackgroundJob,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public static function getHandledMessages(): iterable
    {
        return [ExcludedSubscribersSyncScheduledTask::class];
    }

    public function run(): void
    {
        try {
            $isJobExcludedSubscribersSyncEnabled = $this->systemConfigService->getBool(
                'klavi_overd.config.excludedSubscribersSynchronization'
            );

            if ($isJobExcludedSubscribersSyncEnabled) {
                $this->scheduleBackgroundJob->scheduleEventsDailyExcludedSubscribersProcessingJob();
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
