<?php

namespace Klaviyo\Integration\Async;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\JobSchedulerInterface;
use Klaviyo\Integration\Utils\Specification\SpecificationInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class SubscribersSynchronizationScheduledTaskHandler extends ScheduledTaskHandler
{
    private JobHelper $jobHelper;
    private JobSchedulerInterface $jobScheduler;
    private ConfigurationRegistry $configurationRegistry;
    private SpecificationInterface $stuckJobSpecification;
    private LoggerInterface $logger;

    public function __construct(EntityRepositoryInterface $scheduledTaskRepository,
        JobHelper $jobHelper,
        JobSchedulerInterface $jobScheduler,
        ConfigurationRegistry $configurationRegistry,
        SpecificationInterface $stuckJobSpecification,
        LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->logger = $logger;
        $this->jobHelper = $jobHelper;
        $this->jobScheduler = $jobScheduler;
        $this->configurationRegistry = $configurationRegistry;
        $this->stuckJobSpecification = $stuckJobSpecification;
    }

    public static function getHandledMessages(): iterable
    {
        return [SubscribersSynchronizationScheduledTask::class];
    }

    public function run(): void
    {
        try {
            /**
             * Shopware scheduled tasks interval was not used because:
             * - they are not working properly on all environment
             * (continuously run independent from next schedule date because of the bug:
             * \Shopware\Core\Framework\MessageQueue\ScheduledTask\Scheduler\TaskScheduler::buildCriteriaForAllScheduledTask,
             * \DATE_ATOM fate time format is used in the filter instead of \Shopware\Core\Defaults::STORAGE_DATE_TIME_FORMAT
             * )
             * at least in MySQL 8.0.25 Community Edition
             */
            $this->scheduleSubscribersSynchronizationIfNeeded(Context::createDefaultContext());
        } catch (\Throwable $throwable) {
            $this->logger->error(
                sprintf('Synchronization schedule failed, reason: %s', $throwable->getMessage()),
                [
                    'exception' => $throwable
                ]
            );
        }
    }

    private function scheduleSubscribersSynchronizationIfNeeded(Context $context)
    {
        $defaultConfiguration = $this->configurationRegistry->getDefaultConfiguration();
        $configuredSynchronizationTime = $defaultConfiguration->getSubscribersSynchronizationTime();
        // New date time object created each time to avoid problems in case if the same consumer
        // is working longer than one day
        $scheduleDate = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $scheduleDate = $scheduleDate
            ->setTime($configuredSynchronizationTime->getHour(), $configuredSynchronizationTime->getMinute());
        if (!$this->isTimeToScheduleNewJobHasCome($scheduleDate)) {
            return;
        }
        
        // Order of conditions is important here, self::hasActiveJob is responsible for marking jobs as stuck
        // And next condition will not detect active jobs scheduled by schedule
        if ($this->hasActiveJob($context)) {
            return;
        }

        $hasFinishedJobCreatedByScheduleInCurrentInterval =$this->jobHelper->hasFinishedJobCreatedByScheduleAfter(
            $context,
            JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE,
            $scheduleDate
        );
        if ($hasFinishedJobCreatedByScheduleInCurrentInterval) {
            return;
        }

        $this->jobScheduler->scheduleJob($context, JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE, true);
    }

    /**
     * Check if scheduled time is now or not later than 1 hour ago
     * 1 hour delta is used because consumers could be busy during the schedule time
     *
     * @param \DateTimeImmutable $scheduleJobTime
     *
     * @return bool
     * @throws \Exception
     */
    private function isTimeToScheduleNewJobHasCome(\DateTimeImmutable $scheduleJobTime): bool
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $oneHourLaterThenSchedule = $scheduleJobTime->add(new \DateInterval('PT1H'));

        return $now >= $scheduleJobTime && $now <= $oneHourLaterThenSchedule;
    }
    
    private function hasActiveJob(Context $context): bool
    {
        $lastActiveJob = $this->jobHelper
            ->getLastActiveJob($context, JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE);
        if (!$lastActiveJob) {
            return false;
        }

        if (!$this->stuckJobSpecification->isSatisfiedBy($lastActiveJob)) {
            return true;
        }

        $this->logger->error(
            'Stuck subscribers synchronization job found',
            [
                'id' => $lastActiveJob->getId(),
                'job' => $lastActiveJob
            ]
        );

        $this->jobHelper
            ->markSynchronizationJobAsStuck($context, $lastActiveJob);

        return false;
    }
}