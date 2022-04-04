<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase;

use Klaviyo\Integration\Async\Message;
use Klaviyo\Integration\Exception\{JobAlreadyRunningException, JobAlreadyScheduledException};
use Klaviyo\Integration\Model\UseCase\Operation\{FullOrderSyncOperation, FullSubscriberSyncOperation};
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers\GetExcludedSubscribersResponse;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
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
    public const DEFAULT_COUNT_PER_PAGE = '500';

    private EntityRepositoryInterface $jobRepository;
    private JobScheduler $scheduler;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $klaviyoFlagStorageRepository;
    private KlaviyoGateway $klaviyoGateway;

    public function __construct(
        EntityRepositoryInterface $jobRepository,
        JobScheduler $scheduler,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $klaviyoFlagStorageRepository,
        KlaviyoGateway $klaviyoGateway
    ) {
        $this->jobRepository = $jobRepository;
        $this->scheduler = $scheduler;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->klaviyoFlagStorageRepository = $klaviyoFlagStorageRepository;
        $this->klaviyoGateway = $klaviyoGateway;
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
    public function sendExcludedSubscribers(Context $context, $message)
    {
        /** @var SalesChannelEntity $channel */
        $channels = $this->salesChannelRepository->search(new Criteria(), $context);
        foreach ($channels as $channel) {
            $page = $this->getLastSynchronizedPage($context, $channel);
            foreach ($this->generateExcludedSubscribers($channel, $page) as $result) {
                $this->scheduleExcludedSubscribersSyncJob(
                    $result->getLists()->getElements(),
                    $message->getJobId()
                );
            }
            $this->writeLastSynchronizedPage($context, $result, $channel);
        }
    }

    private function getLastSynchronizedPage(Context $context, SalesChannelEntity $channel): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('key', self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        $page = $this->klaviyoFlagStorageRepository->search($criteria, $context)->getEntities()->first();

        return $page ? (int)$page->getValue() : 0;
    }

    /**
     * @throws \Exception
     */
    private function generateExcludedSubscribers(
        SalesChannelEntity $channel,
        int $page
    ): \Generator {
        $currentPage = $page;
        $result = $this->getExcludedSubscribers($channel, self::DEFAULT_COUNT_PER_PAGE, $page);
        $totalEmailsValue = $result->getTotalEmailsValue();
        $quantityOfPages = $totalEmailsValue == self::DEFAULT_COUNT_PER_PAGE ?
            0 :
            floor($totalEmailsValue / self::DEFAULT_COUNT_PER_PAGE);
        yield $result;

        while ($quantityOfPages > $currentPage) {
            $currentPage++;
            yield $this->getExcludedSubscribers($channel, self::DEFAULT_COUNT_PER_PAGE, $currentPage);
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function getExcludedSubscribers(
        SalesChannelEntity $channel,
        string $count,
        $page
    ): GetExcludedSubscribersResponse {
        return $this->klaviyoGateway->getExcludedSubscribersFromList($channel, $count, $page);
    }

    public function scheduleExcludedSubscribersSyncJob(array $emails, string $parentJobId): void
    {
        $jobMessage = new Message\ExcludedSubscriberSyncMessage(
            Uuid::randomHex(),
            $parentJobId,
            $emails);
        $this->scheduler->schedule($jobMessage);
    }

    private function writeLastSynchronizedPage(
        Context $context,
        GetExcludedSubscribersResponse $result,
        SalesChannelEntity $channel
    ) {
        $emails = array_map(function ($email) {
            return $email->getEmail();
        }, $result->getLists()->getElements());
        $hashEmails = md5(serialize($emails));
        $page = $result->getPage();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('value', $hashEmails));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));
        $isHashValueExists = $this->klaviyoFlagStorageRepository->search($criteria, $context)->getEntities()->first();
        if ($isHashValueExists === null) {
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
}
