<?php
declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleFullCustomerOrderSyncTask;
use Klaviyo\Integration\Model\UseCase\Operation\FullCustomerOrderSyncOperation;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\ConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class ScheduleFullCustomerOrderSyncTaskHandler extends ScheduledTaskHandler
{
    private  ScheduleBackgroundJob $scheduleBackgroundJob;
    private  LoggerInterface $logger;
    private  ConfigService   $configService;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        LoggerInterface $logger,
        ConfigService   $configService
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
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
            $offset = $this->configService->getConfigValueWithoutCache(FullCustomerOrderSyncOperation::SYNC_CUSTOMER_OFFSET_CONFIG_KEY);

            if ($offset >= 0) {
                $this->logger->notice("ScheduleFullCustomerSyncTask started");
                $this->scheduleBackgroundJob->scheduleFullCustomerOrderSyncJob($context);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [ScheduleFullCustomerOrderSyncTask::class];
    }
}
