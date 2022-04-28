<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase;

use Klaviyo\Integration\Async\Message;
use Klaviyo\Integration\Exception\{JobAlreadyRunningException, JobAlreadyScheduledException};
use Klaviyo\Integration\Model\UseCase\Operation\{FullOrderSyncOperation, FullSubscriberSyncOperation};
use Klaviyo\Integration\Entity\Helper\ExcludedSubscribersProvider;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\SyncProgressInfo;
use Klaviyo\Integration\Klaviyo\FrontendApi\ExcludedSubscribers\CreateArrayHash;
use Klaviyo\Integration\Klaviyo\FrontendApi\ExcludedSubscribers\SyncProgressService;
use Od\Scheduler\Entity\Job\JobEntity;
use Od\Scheduler\Model\JobScheduler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{AndFilter, EqualsAnyFilter, EqualsFilter};
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class ScheduleBackgroundJob
{
    private EntityRepositoryInterface $jobRepository;
    private JobScheduler $scheduler;
    private EntityRepositoryInterface $salesChannelRepository;
    private ExcludedSubscribersProvider $excludedSubscribersProvider;
    private SyncProgressService $progressService;

    public function __construct(
        EntityRepositoryInterface $jobRepository,
        JobScheduler $scheduler,
        EntityRepositoryInterface $salesChannelRepository,
        ExcludedSubscribersProvider $excludedSubscribersProvider,
        SyncProgressService $progressService
    ) {
        $this->jobRepository = $jobRepository;
        $this->scheduler = $scheduler;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->excludedSubscribersProvider = $excludedSubscribersProvider;
        $this->progressService = $progressService;
    }

    public function scheduleFullSubscriberSyncJob()
    {
        $this->checkJobStatus(FullSubscriberSyncOperation::OPERATION_HANDLER_CODE);
        $jobMessage = new Message\FullSubscriberSyncMessage(Uuid::randomHex());
        $this->scheduler->schedule($jobMessage);
    }

    private function checkJobStatus(string $type)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new AndFilter([
            new EqualsFilter('type', $type),
            new EqualsAnyFilter('status', [JobEntity::TYPE_PENDING, JobEntity::TYPE_RUNNING])
        ]));
        /** @var JobEntity $job */
        if ($job = $this->jobRepository->search($criteria, Context::createDefaultContext())->first()) {
            if ($job->getStatus() === JobEntity::TYPE_PENDING) {
                throw new JobAlreadyScheduledException('Job is already scheduled.');
            } else {
                throw new JobAlreadyRunningException('Job is already running.');
            }
        }
    }

    public function scheduleSubscriberSyncJob(array $subscriberIds, string $parentJobId)
    {
        $jobMessage = new Message\SubscriberSyncMessage(
            Uuid::randomHex(),
            $parentJobId,
            $subscriberIds
        );

        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleFullOrderSyncJob()
    {
        $this->checkJobStatus(FullOrderSyncOperation::OPERATION_HANDLER_CODE);
        $jobMessage = new Message\FullOrderSyncMessage(Uuid::randomHex());
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleOrderSyncJob(array $orderIds, string $parentJobId)
    {
        $jobMessage = new Message\OrderSyncMessage(Uuid::randomHex(), $parentJobId, $orderIds);
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleOrderEventsSyncJob(array $eventIds, string $parentJobId)
    {
        $jobMessage = new Message\OrderEventSyncMessage(Uuid::randomHex(), $parentJobId, $eventIds);
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleCartEventsSyncJob(array $eventRequestIds, string $parentJobId)
    {
        $jobMessage = new Message\CartEventSyncMessage(Uuid::randomHex(), $parentJobId, $eventRequestIds);
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleEventsProcessingJob()
    {
        $jobMessage = new Message\EventsProcessingMessage(Uuid::randomHex());
        $this->scheduler->schedule($jobMessage);
    }

    public function scheduleCustomerProfilesSyncJob(array $customerIds, string $parentJobId)
    {
        $jobMessage = new Message\CustomerProfileSyncMessage(Uuid::randomHex(), $parentJobId, $customerIds);
        $this->scheduler->schedule($jobMessage);
    }

    /**
     * @param Context $context
     * @param string $parentJobId
     * @return \Exception[]
     */
    public function scheduleExcludedSubscribersSyncJobs(Context $context, string $parentJobId): array
    {
        $errors = [];
        /** @var SalesChannelEntity $channel */
        $channels = $this->salesChannelRepository->search(new Criteria(), $context);
        foreach ($channels as $channel) {
            $isFirstLoadedPage = true;
            $syncInfo = $this->progressService->get($context, $channel);

            try {
                foreach ($this->excludedSubscribersProvider->getExcludedSubscribers($channel, $syncInfo->getPage()) as $result) {
                    if ($isFirstLoadedPage) {
                        $isFirstLoadedPage = false;
                        if ($syncInfo->getHash() === CreateArrayHash::execute($result->getEmails())) {
                            continue 2;
                        }
                    }

                    $jobMessage = new Message\ExcludedSubscriberSyncMessage(
                        Uuid::randomHex(),
                        $parentJobId,
                        $result->getEmails(),
                        $channel->getId()
                    );
                    $this->scheduler->schedule($jobMessage);
                }

                if (isset($result)) {
                    $syncInfo->setPage($result->getPage());
                    $syncInfo->setHash(CreateArrayHash::execute($result->getEmails()));
                    $this->progressService->save($context, $syncInfo);
                }
            } catch (\Exception $e) {
                $errors[] = $e;
            }
        }

        return $errors;
    }
}
