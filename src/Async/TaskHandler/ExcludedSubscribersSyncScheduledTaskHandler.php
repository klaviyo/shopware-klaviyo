<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ExcludedSubscribersSyncScheduledTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ExcludedSubscribersSyncScheduledTaskHandler extends ScheduledTaskHandler
{
    private LoggerInterface $logger;
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private SystemConfigService $systemConfigService;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository);

        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
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
