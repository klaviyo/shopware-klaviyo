<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleFullHistoricalSyncTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\ConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Klaviyo\Integration\Model\UseCase\Operation\FullOrderSyncOperation;

#[AsMessageHandler(handles: ScheduleFullHistoricalSyncTask::class)]
final class ScheduleFullHistoricalSyncTaskHandler extends ScheduledTaskHandler
{
    /**
     * @param EntityRepository $scheduledTaskRepository
     * @param ScheduleBackgroundJob $scheduleBackgroundJob
     * @param LoggerInterface $logger
     * @param ConfigService $configService
     */
    public function __construct(
        protected EntityRepository $scheduledTaskRepository,
        private readonly ScheduleBackgroundJob $scheduleBackgroundJob,
        private readonly LoggerInterface $logger,
        private readonly ConfigService   $configService
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
            $offset = $this->configService->getConfigValueWithoutCache(FullOrderSyncOperation::SYNC_ORDER_OFFSET_CONFIG_KEY);

            if ($offset >= 0) {
                $this->logger->notice("ScheduleFullHistoricalSyncTask started");
                $this->scheduleBackgroundJob->scheduleFullOrderSyncJob($context);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
