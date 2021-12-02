<?php

namespace Klaviyo\Integration\Async;

use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class ClearOldJobsScheduledTaskHandler extends ScheduledTaskHandler
{
    private const START_CLEAR_AFTER_JOBS_QUANTITY_REACH = 30000;
    private const QUANTITY_OF_NEWEST_RECORDS_LEFT_AFTER_CLEAR = 2000;

    private JobHelper $jobHelper;
    private LoggerInterface $logger;

    public function __construct(EntityRepositoryInterface $scheduledTaskRepository, JobHelper $jobHelper, LoggerInterface $logger)
    {
        parent::__construct($scheduledTaskRepository);
        $this->jobHelper = $jobHelper;
        $this->logger = $logger;
    }

    public function run(): void
    {
        try {
            $context = Context::createDefaultContext();
            $this->clearRecordsForIntegration($context, JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE);
            $this->clearRecordsForIntegration($context, JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                sprintf('Jobs clear failed, reason: %s', $throwable->getMessage()),
                [
                    'exception' => $throwable
                ]
            );
        }
    }

    private function clearRecordsForIntegration(Context $context, string $type)
    {
        try {
            $count = $this->jobHelper->getJobsCount($context, $type);
            if ($count > self::START_CLEAR_AFTER_JOBS_QUANTITY_REACH) {
                $this->logger->warning(
                    sprintf(
                        'Job entities quantity exceed maximum allowed amount (%s), ' .
                        'attempting to remove outdated records',
                        self::START_CLEAR_AFTER_JOBS_QUANTITY_REACH
                    ),
                );
                $this->jobHelper->removeOldJobs(
                    $context,
                    $type,
                    self::QUANTITY_OF_NEWEST_RECORDS_LEFT_AFTER_CLEAR
                );
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('Jobs[type: %s] clear failed, reason: %s', $type, $exception->getMessage()),
                [
                    'exception' => $exception
                ]
            );
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [ClearOldJobsScheduledTask::class];
    }
}