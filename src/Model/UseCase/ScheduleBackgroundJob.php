<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase;

use Klaviyo\Integration\Async\Message;
use Klaviyo\Integration\Exception\{JobAlreadyRunningException, JobAlreadyScheduledException};
use Klaviyo\Integration\Model\UseCase\Operation\{FullOrderSyncOperation, FullSubscriberSyncOperation};
use Klaviyo\Integration\Entity\Helper\ExcludedSubscribersProvider;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers\Response;
use Od\Scheduler\Entity\Job\JobEntity;
use Od\Scheduler\Model\JobScheduler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{AndFilter, EqualsAnyFilter, EqualsFilter};
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class ScheduleBackgroundJob
{
    public const LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE = 'last_synchronized_unsubscribers_page';
    public const LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE_HASH = 'last_synchronized_unsubscribers_page_hash';

    private EntityRepositoryInterface $jobRepository;
    private JobScheduler $scheduler;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $klaviyoFlagStorageRepository;
    private ExcludedSubscribersProvider $excludedSubscribersProvider;

    public function __construct(
        EntityRepositoryInterface $jobRepository,
        JobScheduler $scheduler,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $klaviyoFlagStorageRepository,
        ExcludedSubscribersProvider $excludedSubscribersProvider
    ) {
        $this->jobRepository = $jobRepository;
        $this->scheduler = $scheduler;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->klaviyoFlagStorageRepository = $klaviyoFlagStorageRepository;
        $this->excludedSubscribersProvider = $excludedSubscribersProvider;
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
     * @throws \Exception
     */
    public function sendExcludedSubscribers(Context $context, string $jobId)
    {
        /** @var SalesChannelEntity $channel */
        $channels = $this->salesChannelRepository->search(new Criteria(), $context);
        foreach ($channels as $channel) {
            $isFirstLoadedPage = true;
            $pageAndHash = $this->getLastSynchronizedPageAndHash($context, $channel);
            $page = $pageAndHash ? (int)$pageAndHash[self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE] : 0;
            $hash = $pageAndHash ? $pageAndHash[self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE_HASH] : '';

            foreach ($this->excludedSubscribersProvider->getExcludedSubscribers($channel, $page) as $result) {
                if ($isFirstLoadedPage) {
                    $hashEmails = md5(serialize($result->getEmails()));
                    $isFirstLoadedPage = false;
                    if ($hash === $hashEmails) {
                        continue 2;
                    }
                }
                $this->scheduleExcludedSubscribersSyncJob(
                    $result->getEmails(),
                    $jobId
                );
                if (
                    count($result->getEmails()) <
                    $this->excludedSubscribersProvider::DEFAULT_COUNT_PER_PAGE
                ) {
                    $this->writeLastSynchronizedPage($context, $result, $channel);
                }
            }
        }
    }

    private function getLastSynchronizedPageAndHash(Context $context, SalesChannelEntity $channel): ?array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(2);

        $klaviyoFlags = $this->klaviyoFlagStorageRepository->search($criteria, $context)->getEntities();
        $lastPageAndHash = [];
        foreach ($klaviyoFlags ?? [] as $flag) {
            $lastPageAndHash[$flag->getKey()] = $flag->getValue();
        }

        return $lastPageAndHash;
    }

    public function scheduleExcludedSubscribersSyncJob(array $emails, string $parentJobId): void
    {
        $jobMessage = new Message\ExcludedSubscriberSyncMessage(
            Uuid::randomHex(),
            $parentJobId,
            $emails
        );
        $this->scheduler->schedule($jobMessage);
    }

    private function writeLastSynchronizedPage(
        Context $context,
        Response $result,
        SalesChannelEntity $channel
    ) {
        $hashEmails = md5(serialize($result->getEmails()));
        $page = $result->getPage();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('value', $hashEmails));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));
        $this->klaviyoFlagStorageRepository->create([
            [
                'id' => Uuid::randomHex(),
                'key' => self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE,
                'value' => $page,
                'salesChannelId' => $channel->getId()
            ],
            [
                'id' => Uuid::randomHex(),
                'key' => self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE_HASH,
                'value' => $hashEmails,
                'salesChannelId' => $channel->getId()
            ]
        ], $context);
    }
}
