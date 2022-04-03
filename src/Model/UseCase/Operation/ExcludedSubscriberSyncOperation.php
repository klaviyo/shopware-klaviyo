<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers\GetExcludedSubscribersResponse;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult};
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class ExcludedSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-excluded-subscriber-sync-handler';
    public const LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE = 'last_synchronized_unsubscribers_page';
    public const LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE_HASH = 'last_synchronized_unsubscribers_page_hash';
    public const DEFAULT_COUNT_PER_PAGE = '500';

    private EntityRepositoryInterface $newsletterRepository;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $klaviyoFlagStorageRepository;
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private KlaviyoGateway $klaviyoGateway;

    public function __construct(
        EntityRepositoryInterface $newsletterRepository,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $klaviyoFlagStorageRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        KlaviyoGateway $klaviyoGateway
    ) {
        $this->newsletterRepository = $newsletterRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->klaviyoFlagStorageRepository = $klaviyoFlagStorageRepository;
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->klaviyoGateway = $klaviyoGateway;
    }

    public function execute(object $message): JobResult
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $emails = array_map(function ($email) {
            return $email->getEmail();
        }, $message->getEmails());
        $criteria->addFilter(new EqualsAnyFilter('email', $emails));
        $subscribers = $this->newsletterRepository->search($criteria, $context);
        $subscriberData = [];
        foreach ($subscribers as $subscriber) {
            $subscriberData[] = [
                'id' => $subscriber->getId(),
                'email' => $subscriber->getEmail(),
                'status' => NewsletterSubscribeRoute::STATUS_OPT_OUT
            ];
        }
        $this->newsletterRepository->update($subscriberData, $context);

        return new JobResult();
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
            foreach ($this->generateExcludedSubscribers($context, $channel, $page) as $result) {
                $this->runExcludedSubscribersScheduledBackgroundJob($result, $message);
            }
        }
    }

    private function getLastSynchronizedPage(Context $context, SalesChannelEntity $channel): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('key', self::LAST_SYNCHRONIZED_UNSUBSCRIBERS_PAGE));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getId()));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        $page = $this->klaviyoFlagStorageRepository->search($criteria, $context)->getEntities()->first();

        return $page ? (string)$page->getValue() : '0';
    }

    /**
     * @throws \Exception
     */
    private function generateExcludedSubscribers(
        Context $context,
        SalesChannelEntity $channel,
        string $page
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
            $result = $this->getExcludedSubscribers($channel, self::DEFAULT_COUNT_PER_PAGE, $currentPage);
            yield $this->getExcludedSubscribers($channel, self::DEFAULT_COUNT_PER_PAGE, $currentPage);
        }

        $this->writeLastSynchronizedPage($context, $result, $channel);
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

    private function runExcludedSubscribersScheduledBackgroundJob(
        GetexcludedSubscribersResponse $result,
        $message
    ) {
        $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJob(
            $result->getLists()->getElements(),
            $message->getJobId()
        );
    }
}