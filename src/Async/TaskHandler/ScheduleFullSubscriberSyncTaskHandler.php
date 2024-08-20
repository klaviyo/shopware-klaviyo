<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleFullSubscriberSyncTask;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: ScheduleFullSubscriberSyncTask::class)]
final class ScheduleFullSubscriberSyncTaskHandler extends ScheduledTaskHandler
{
    /**
     * @param EntityRepository $scheduledTaskRepository
     * @param ScheduleBackgroundJob $scheduleBackgroundJob
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected EntityRepository $scheduledTaskRepository,
        private readonly ScheduleBackgroundJob $scheduleBackgroundJob,
        private readonly LoggerInterface $logger,
        private readonly SystemConfigService $systemConfigService
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
            $offset = $this->systemConfigService->get('klavi_overd.cron.fullSubscriberSyncOffset');
            if ($offset >= 0) {
                $this->logger->notice("ScheduleFullSubscriberSyncTask started");
                $this->scheduleBackgroundJob->scheduleFullSubscriberSyncJob($context);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
