<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase;

use Klaviyo\Integration\Async\Message;
use Klaviyo\Integration\Entity\Helper\ExcludedSubscribersProvider;
use Klaviyo\Integration\Exception\JobAlreadyRunningException;
use Klaviyo\Integration\Exception\JobAlreadyScheduledException;
use Klaviyo\Integration\Model\UseCase\Operation\FullOrderSyncOperation;
use Klaviyo\Integration\Model\UseCase\Operation\FullSubscriberSyncOperation;
use Klaviyo\Integration\System\Scheduling\ExcludedSubscriberSync;
use Od\Scheduler\Entity\Job\JobEntity;
use Od\Scheduler\Model\JobScheduler;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class ScheduleBackgroundJob
{
    private EntityRepository $jobRepository;
    private JobScheduler $scheduler;
    private ExcludedSubscribersProvider $excludedSubscribersProvider;
    private LoggerInterface $logger;
    private EntityRepository $subscriberRepository;

    public function __construct(
        EntityRepository $jobRepository,
        JobScheduler $scheduler,
        ExcludedSubscribersProvider $excludedSubscribersProvider,
        EntityRepository $subscriberRepository,
        LoggerInterface $logger
    ) {
        $this->jobRepository = $jobRepository;
        $this->scheduler = $scheduler;
        $this->excludedSubscribersProvider = $excludedSubscribersProvider;
        $this->subscriberRepository = $subscriberRepository;
        $this->logger = $logger;
    }

    /**
     * @throws JobAlreadyRunningException
     * @throws JobAlreadyScheduledException
     */
    public function scheduleFullSubscriberSyncJob(Context $context): void
    {
        $this->checkJobStatus(FullSubscriberSyncOperation::OPERATION_HANDLER_CODE, $context);
        $jobMessage = new Message\FullSubscriberSyncMessage(Uuid::randomHex(), null, $context);
        $this->scheduler->schedule($jobMessage);
    }

    /**
     * @throws JobAlreadyRunningException
     * @throws JobAlreadyScheduledException
     */
    private function checkJobStatus(string $type, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new AndFilter([
            new EqualsFilter('type', $type),
            new EqualsAnyFilter('status', [JobEntity::TYPE_PENDING, JobEntity::TYPE_RUNNING]),
        ]));

        /** @var JobEntity $job */
        if ($job = $this->jobRepository->search($criteria, $context)->first()) {
            if (JobEntity::TYPE_PENDING === $job->getStatus()) {
                throw new JobAlreadyScheduledException('Job is already scheduled.');
            } else {
                throw new JobAlreadyRunningException('Job is already running.');
            }
        }
    }

    public function scheduleSubscriberSyncJob(
        array $subscriberIds,
        string $parentJobId,
        Context $context,
        string $name = null
    ): void {
        $jobMessage = new Message\SubscriberSyncMessage(
            Uuid::randomHex(),
            $parentJobId,
            $subscriberIds,
            $name,
            $context
        );

        $this->scheduler->schedule($jobMessage);
    }

    /**
     * @throws JobAlreadyRunningException
     * @throws JobAlreadyScheduledException
     */
    public function scheduleFullOrderSyncJob(Context $context): void
    {
        $this->checkJobStatus(FullOrderSyncOperation::OPERATION_HANDLER_CODE, $context);
        $jobMessage = new Message\FullOrderSyncMessage(Uuid::randomHex(), null, $context);
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleOrderSyncJob(array $orderIds, string $parentJobId, Context $context): void
    {
        $jobMessage = new Message\OrderSyncMessage(Uuid::randomHex(), $parentJobId, $orderIds, null, $context);
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleOrderEventsSyncJob(array $eventIds, string $parentJobId, Context $context): void
    {
        $jobMessage = new Message\OrderEventSyncMessage(Uuid::randomHex(), $parentJobId, $eventIds, null, $context);
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleCartEventsSyncJob(array $eventRequestIds, string $parentJobId, Context $context): void
    {
        $jobMessage = new Message\CartEventSyncMessage(
            Uuid::randomHex(),
            $parentJobId,
            $eventRequestIds,
            null,
            $context
        );

        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleEventsProcessingJob(): void
    {
        // Here we have context-less process
        $jobMessage = new Message\EventsProcessingMessage(Uuid::randomHex());
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleEventsDailyExcludedSubscribersProcessingJob(): void
    {
        $jobMessage = new Message\DailyExcludedSubscriberSyncMessage(Uuid::randomHex());
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleCustomerProfilesSyncJob(array $customerIds, string $parentJobId, Context $context): void
    {
        $jobMessage = new Message\CustomerProfileSyncMessage(
            Uuid::randomHex(),
            $parentJobId,
            $customerIds,
            null,
            $context
        );

        $this->scheduler->schedule($jobMessage);
    }

    /**
     * @param Context $context
     * @param string $parentJobId
     * @param string[] $channelIds
     * @return ExcludedSubscriberSync\Result
     */
    public function scheduleExcludedSubscribersSyncJobs(
        Context $context,
        string $parentJobId,
        array $channelIds
    ): ExcludedSubscriberSync\Result {
        $schedulingResult = new ExcludedSubscriberSync\Result();

        foreach ($channelIds as $channelId) {
            try {
                foreach ($this->excludedSubscribersProvider->getExcludedSubscribers($channelId) as $result) {
                    if (!count($result->getEmails())) {
                        continue;
                    }

                    $excludedSubscriberIds = [];
                    $resultEmails = $result->getEmails();

                    $excludedCriteria = new Criteria();
                    $excludedCriteria->addFilter(new EqualsFilter('salesChannelId', $channelId));
                    $excludedCriteria->addFilter(new EqualsAnyFilter('email', $result->getEmails()));

                    $excludedSubscribers = $this->subscriberRepository->search(
                        $excludedCriteria,
                        $context
                    )->map(fn ($entity) => $entity->getEmail());

                    if (!empty($excludedSubscribers)) {
                        $excludedSubscriberIds = \array_merge(
                            $excludedSubscriberIds,
                            \array_keys($excludedSubscribers)
                        );

                        $resultEmails = \array_values($excludedSubscribers);
                    }

                    if (!empty($excludedSubscriberIds)) {
                        $jobMessage = new Message\ExcludedSubscriberSyncMessage(
                            Uuid::randomHex(),
                            $parentJobId,
                            $resultEmails,
                            $channelId,
                            null,
                            $context
                        );
                        $this->scheduler->schedule($jobMessage);

                        $schedulingResult->addSubscriberIds($channelId, $excludedSubscriberIds);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $schedulingResult->addError(
                    new \Exception('Something wrong with the excluded subscribers sync event')
                );
            }
        }

        return $schedulingResult;
    }
}
