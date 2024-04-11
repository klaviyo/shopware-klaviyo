<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase;

use Klaviyo\Integration\Async\Message;
use Klaviyo\Integration\Entity\Helper\ExcludedSubscribersProvider;
use Klaviyo\Integration\Exception\{JobAlreadyRunningException, JobAlreadyScheduledException};
use Klaviyo\Integration\Klaviyo\FrontendApi\ExcludedSubscribers\SyncProgressService;
use Klaviyo\Integration\Model\UseCase\Operation\{FullOrderSyncOperation, FullSubscriberSyncOperation};
use Klaviyo\Integration\System\Scheduling\ExcludedSubscriberSync;
use Od\Scheduler\Entity\Job\JobEntity;
use Od\Scheduler\Model\JobScheduler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{AndFilter, EqualsAnyFilter, EqualsFilter};
use Shopware\Core\Framework\Uuid\Uuid;
use Psr\Log\LoggerInterface;

class ScheduleBackgroundJob
{
    private EntityRepositoryInterface $jobRepository;
    private JobScheduler $scheduler;
    private ExcludedSubscribersProvider $excludedSubscribersProvider;
    private SyncProgressService $progressService;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $jobRepository,
        JobScheduler $scheduler,
        ExcludedSubscribersProvider $excludedSubscribersProvider,
        SyncProgressService $progressService,
        LoggerInterface $logger
    ) {
        $this->jobRepository = $jobRepository;
        $this->scheduler = $scheduler;
        $this->excludedSubscribersProvider = $excludedSubscribersProvider;
        $this->progressService = $progressService;
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
            new EqualsAnyFilter('status', [JobEntity::TYPE_PENDING, JobEntity::TYPE_RUNNING])
        ]));
        /** @var JobEntity $job */
        if ($job = $this->jobRepository->search($criteria, $context)->first()) {
            if ($job->getStatus() === JobEntity::TYPE_PENDING) {
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
    ) {
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

    public function scheduleEventsProcessingJob()
    {
        // Here we have context-less process
        $jobMessage = new Message\EventsProcessingMessage(Uuid::randomHex());
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
                    $jobMessage = new Message\ExcludedSubscriberSyncMessage(
                        Uuid::randomHex(),
                        $parentJobId,
                        $result->getEmails(),
                        $channelId,
                        null,
                        $context
                    );
                    $this->scheduler->schedule($jobMessage);
                    $schedulingResult->addEmails($channelId, $result->getEmails());
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
