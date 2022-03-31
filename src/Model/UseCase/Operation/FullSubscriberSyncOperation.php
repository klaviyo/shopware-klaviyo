<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\FullSubscriberSyncMessage;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribersResponse;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult};
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{EqualsAnyFilter, EqualsFilter};
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class FullSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-subscriber-sync-handler';
    private const SUBSCRIBER_BATCH_SIZE = 100;

    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepositoryInterface $subscriberRepository;
    private KlaviyoGateway $klaviyoGateway;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $klaviyoFlagStorageRepository;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepositoryInterface $subscriberRepository,
        KlaviyoGateway $klaviyoGateway,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $klaviyoFlagStorageRepository
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->subscriberRepository = $subscriberRepository;
        $this->klaviyoGateway = $klaviyoGateway;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->klaviyoFlagStorageRepository = $klaviyoFlagStorageRepository;
    }

    /**
     * @param FullSubscriberSyncMessage $message
     */
    public function execute(object $message): JobResult
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(self::SUBSCRIBER_BATCH_SIZE);
        $criteria->addFilter(
            new EqualsAnyFilter(
                'status',
                [
                    NewsletterSubscribeRoute::STATUS_OPT_OUT,
                    NewsletterSubscribeRoute::STATUS_OPT_IN,
                    NewsletterSubscribeRoute::STATUS_DIRECT
                ]
            )
        );
        $iterator = new RepositoryIterator($this->subscriberRepository, $context, $criteria);
        //TODO move logic to separate functions
        $context = Context::createDefaultContext();
        /** @var SalesChannelEntity $channel */
        $channels = $this->salesChannelRepository->search(new Criteria(), $context);
        $result = new JobResult();
        $page = $this->getLastSynchronizedPage($context);
        foreach ($channels as $channel) {
            try {
                $result = $this->getExcludedSubscribers($channel, $page);
            } catch (\Throwable $e) {
                $result->addError($e);
            }
        }

        $emails = array_map(function ($email) {
            return $email->getEmail();
        }, $result->getLists()->getElements());

        $hashEmails = md5(serialize($emails));
        $page = $result->getPage();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('hash', $hashEmails));
        $isHashValueExists = $this->klaviyoFlagStorageRepository->search($criteria, $context)->getEntities()->first();
        if ($isHashValueExists === null) {
            $this->klaviyoFlagStorageRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'key' => 'last_synchronized_unsubscribers_page',
                    'value' => $page,
                    'hash' => $hashEmails
                ]
            ], $context);
        }

        $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJob($result->getLists()->getElements(),
            $message->getJobId());

        while (($subscriberIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(
                $subscriberIds,
                $message->getJobId()
            );
        }

        return new JobResult();
    }

    /**
     * @throws \Exception
     */
    public function getExcludedSubscribers($channel, $page): GetExcludedSubscribersResponse
    {
        return $this->klaviyoGateway->getExcludedSubscribersFromList($channel, $page);
    }

    private function getLastSynchronizedPage(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        return $this->klaviyoFlagStorageRepository->search($criteria, $context)->getEntities()->first()->getValue() ?? '0';
    }
}
