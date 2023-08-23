<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\EventsProcessingMessage;
use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Od\Scheduler\Model\Job\Message;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class EventsProcessingOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const HANDLER_CODE = 'od-klaviyo-events-sync-handler';
    public const REALTIME_SUBSCRIBERS_OPERATION_LABEL = 'real-time-subscribers-sync-operation';

    private EntityRepository $eventRepository;
    private EntityRepository $cartEventRequestRepository;
    private EntityRepository $subscriberRepository;
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private GetValidChannels $getValidChannels;

    public function __construct(
        EntityRepository $eventRepository,
        EntityRepository $cartEventRequestRepository,
        EntityRepository $subscriberRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        GetValidChannels $getValidChannels
    ) {
        $this->eventRepository = $eventRepository;
        $this->cartEventRequestRepository = $cartEventRequestRepository;
        $this->subscriberRepository = $subscriberRepository;
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->getValidChannels = $getValidChannels;
    }

    /**
     * @param EventsProcessingMessage $message
     *
     * @return JobResult
     * @throws \Exception
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $context = $message->getContext();
        $channelIds = $this->getValidChannels->execute($context)->map(
            fn (SalesChannelEntity $channel) => $channel->getId()
        );
        $channelIds = \array_values($channelIds);

        if (empty($channelIds)) {
            $result->addMessage(new Message\WarningMessage('There are no configured channels - skipping.'));

            return $result;
        }

        $orderTotal = $this->processOrderEvents($context, $message->getJobId(), $channelIds);
        $cartTotal = $this->processCartEvents($context, $message->getJobId(), $channelIds);
        $customerTotal = $this->processCustomerProfileEvents($context, $message->getJobId(), $channelIds);
        $schedulingResult = $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJobs(
            $context,
            $message->getJobId(),
            $channelIds
        );
        $subscriberTotal = $this->processSubscriberEvents(
            $context,
            $message->getJobId(),
            $channelIds,
            $schedulingResult->all()
        );

        $result->addMessage(new Message\InfoMessage(\sprintf('Total %s order events was scheduled.', $orderTotal)));
        $result->addMessage(new Message\InfoMessage(\sprintf('Total %s cart events was scheduled.', $cartTotal)));
        $result->addMessage(
            new Message\InfoMessage(\sprintf('Total %s customer events was scheduled.', $customerTotal))
        );
        $result->addMessage(
            new Message\InfoMessage(\sprintf('Total %s subscriber events was scheduled.', $subscriberTotal))
        );

        foreach ($schedulingResult->getErrors() as $error) {
            $result->addError($error);
        }

        return $result;
    }

    private function processCustomerProfileEvents(Context $context, string $parentJobId, array $channelIds): int
    {
        $total = 0;
        $iterator = $this->getEventRepoIterator(
            $context,
            [EventsTrackerInterface::CUSTOMER_WRITTEN_EVENT],
            $channelIds
        );

        while (($events = $iterator->fetch()) !== null) {
            $customerIds = $events->map(fn(EventEntity $event) => $event->getEntityId());
            $customerIds = array_values(array_unique($customerIds));
            $total += \count($customerIds);
            $this->scheduleBackgroundJob->scheduleCustomerProfilesSyncJob($customerIds, $parentJobId, $context);
            $this->deleteProcessedEvents($context, $events->getEntities());
        }

        return $total;
    }

    private function processCartEvents(Context $context, string $parentJobId, array $channelIds): int
    {
        $total = 0;
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $channelIds));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);
        $iterator = new RepositoryIterator($this->cartEventRequestRepository, $context, $criteria);

        while (($eventRequestIds = $iterator->fetchIds()) !== null) {
            $total += \count($eventRequestIds);
            $this->scheduleBackgroundJob->scheduleCartEventsSyncJob($eventRequestIds, $parentJobId, $context);
        }

        return $total;
    }

    private function processOrderEvents(Context $context, string $parentJobId, array $channelIds): int
    {
        $total = 0;
        $iterator = $this->getEventRepoIterator(
            $context,
            \array_keys(EventsTrackerInterface::ORDER_EVENTS),
            $channelIds
        );

        while (($eventIds = $iterator->fetchIds()) !== null) {
            $total += \count($eventIds);
            $this->scheduleBackgroundJob->scheduleOrderEventsSyncJob($eventIds, $parentJobId, $context);
        }

        return $total;
    }

    private function processSubscriberEvents(
        Context $context,
        string $parentJobId,
        array $channelIds,
        array $excludedEmailsMap
    ): int {
        $total = 0;
        /**
         * Ensure we will not process unsubscribed customers from backlog.
         * Additionally prepare unsubscribed recipient ids using channel_id to cover cases when there are more than one
         * recipient with same email across multiple channels (including different Klaviyo lists)
         */
        $excludedSubscriberIds = [];
        foreach ($excludedEmailsMap as $channelId => $emails) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('salesChannelId', $channelId));
            $criteria->addFilter(new EqualsAnyFilter('email', $emails));
            $excludedSubscriberIds = \array_merge(
                $excludedSubscriberIds,
                \array_values($this->subscriberRepository->searchIds($criteria, $context)->getIds())
            );
        }

        $iterator = $this->getEventRepoIterator($context, EventsTrackerInterface::SUBSCRIBER_EVENTS, $channelIds);

        while (($events = $iterator->fetch()) !== null) {
            $subscriberIds = $events->map(fn (EventEntity $event) => $event->getEntityId());
            $subscriberIds = \array_values(\array_diff($subscriberIds, $excludedSubscriberIds));
            $total += \count($subscriberIds);
            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(
                $subscriberIds,
                $parentJobId,
                $context,
                self::REALTIME_SUBSCRIBERS_OPERATION_LABEL
            );
            $this->deleteProcessedEvents($context, $events->getEntities());
        }

        return $total;
    }

    private function deleteProcessedEvents(Context $context, EntityCollection $events)
    {
        $deleteDataSet = array_map(function ($id) {
            return ['id' => $id];
        }, array_values($events->getIds()));
        $this->eventRepository->delete($deleteDataSet, $context);
    }

    private function getEventRepoIterator(Context $context, array $eventTypes, array $channelIds)
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('type', $eventTypes),
            new EqualsAnyFilter('salesChannelId', $channelIds)
        );
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);

        return new RepositoryIterator($this->eventRepository, $context, $criteria);
    }
}
