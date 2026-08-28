<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleFullSubscriberSyncTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\ConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Klaviyo\Integration\Model\UseCase\Operation\FullSubscriberSyncOperation;

class ScheduleFullSubscriberSyncTaskHandler extends ScheduledTaskHandler
{
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private LoggerInterface $logger;
    private ConfigService $configService;

    /**
     * @param EntityRepository $scheduledTaskRepository
     * @param ScheduleBackgroundJob $scheduleBackgroundJob
     * @param LoggerInterface $logger
     * @param ConfigService $configService
     */
    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        LoggerInterface $logger,
        ConfigService   $configService
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->logger = $logger;
        $this->configService = $configService;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        try {
            $context = new Context(new SystemSource());
            $offset = $this->configService->getConfigValueWithoutCache(FullSubscriberSyncOperation::SYNC_SUBSCRIBER_OFFSET_CONFIG_KEY);

            if ($offset !== null && $offset >= 0) {
                $this->logger->notice("ScheduleFullSubscriberSyncTask started");
                $this->scheduleBackgroundJob->scheduleFullSubscriberSyncJob($context);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [ScheduleFullSubscriberSyncTask::class];
    }
}
