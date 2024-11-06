<?php
declare(strict_types=1);

namespace Klaviyo\Integration\Async\TaskHandler;

use Klaviyo\Integration\Async\Task\ScheduleFullCustomerSubsSyncTask;
use Klaviyo\Integration\Model\UseCase\Operation\FullCustomerSubsSyncOperation;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\ConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class ScheduleFullCustomerSubsSyncTaskHandler extends ScheduledTaskHandler
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
            $offset = $this->configService->getConfigValueWithoutCache(FullCustomerSubsSyncOperation::SYNC_CUSTOMER_OFFSET_CONFIG_KEY);

            if ($offset >= 0) {
                $this->logger->notice("ScheduleFullCustomerSyncTask started");
                $this->scheduleBackgroundJob->scheduleFullCustomerSubsSyncJob($context);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [ScheduleFullCustomerSubsSyncTask::class];
    }
}
