<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleFullHistoricalSyncTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;


class ScheduleFullHistoricalSyncTaskHandler extends ScheduledTaskHandler
{
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;

    /**
     * @param EntityRepository $scheduledTaskRepository
     * @param ScheduleBackgroundJob $scheduleBackgroundJob
     * @param LoggerInterface $logger
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        LoggerInterface $logger,
        SystemConfigService $systemConfigService
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        try {
            $context = new Context(new SystemSource());
            $offset = $this->systemConfigService->get('klavi_overd.cron.fullOrderSyncOffset');
            if ($offset >= 0) {
                $this->logger->notice("ScheduleFullHistoricalSyncTask started");
                $this->scheduleBackgroundJob->scheduleFullOrderSyncJob($context);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [ScheduleFullHistoricalSyncTask::class];
    }
}
