<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\EventsProcessingMessage;
use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Klaviyo\Integration\Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Klaviyo\Integration\Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult, Message};
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\{EntityCollection, EntityRepositoryInterface};
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class EventsProcessingOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const HANDLER_CODE = 'od-klaviyo-events-sync-handler';
    public const REALTIME_SUBSCRIBERS_OPERATION_LABEL = 'real-time-subscribers-sync-operation';

    /**
     * @var JobResult
     */
    private JobResult $jobResult;

    /**
     * @var EntityRepositoryInterface
     */
    private EntityRepositoryInterface $eventRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private EntityRepositoryInterface $cartEventRequestRepository;

    /**
     * @var ScheduleBackgroundJob
     */
    private ScheduleBackgroundJob $scheduleBackgroundJob;

    /**
     * @var GetValidChannels
     */
    private GetValidChannels $getValidChannels;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param EntityRepositoryInterface $eventRepository
     * @param EntityRepositoryInterface $cartEventRequestRepository
     * @param ScheduleBackgroundJob $scheduleBackgroundJob
     * @param GetValidChannels $getValidChannels
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityRepositoryInterface $eventRepository,
        EntityRepositoryInterface $cartEventRequestRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        GetValidChannels $getValidChannels,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->cartEventRequestRepository = $cartEventRequestRepository;
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->getValidChannels = $getValidChannels;
        $this->logger = $logger;
    }

    /**
     * @param EventsProcessingMessage $message
     *
     * @return JobResult
     * @throws \Exception
     */
    public function execute(object $message): JobResult
    {
        $this->jobResult = new JobResult();
        $context = $message->getContext();
        $channelIds = $this->getValidChannels
            ->execute($context)
            ->map(
                fn(SalesChannelEntity $channel) => $channel->getId()
            );
        $channelIds = \array_values($channelIds);

        if (empty($channelIds)) {
            $this->jobResult->addMessage(
                new Message\WarningMessage('There are no configured channels - skipping.')
            );
            return $this->jobResult;
        }

        $this->processOrderEvents($context, $message->getJobId(), $channelIds);
        $this->processCartEvents($context, $message->getJobId(), $channelIds);
        $this->processCustomerProfileEvents($context, $message->getJobId(), $channelIds);
        $this->processSubscriberEvents($context, $message->getJobId(), $channelIds);

        return $this->jobResult;
    }

    /**
     * @param Context $context
     * @param string $parentJobId
     * @param array $channelIds
     *
     * @return void
     */
    private function processCustomerProfileEvents(Context $context, string $parentJobId, array $channelIds): void
    {
        $total = 0;
        $iterator = $this->getEventRepoIterator(
            $context,
            [EventsTrackerInterface::CUSTOMER_WRITTEN_EVENT],
            $channelIds
        );

        while (($events = $iterator->fetch()) !== null) {
            $customerIds = $events->map(fn (EventEntity $event) => $event->getEntityId());
            $customerIds = array_values(array_unique($customerIds));
            $total += \count($customerIds);
            $this->scheduleBackgroundJob->scheduleCustomerProfilesSyncJob($customerIds, $parentJobId, $context);
            $this->deleteProcessedEvents($context, $events->getEntities());
        }

        if ($total > 0) {
            $this->jobResult->addMessage(
                new Message\InfoMessage(\sprintf('Total %s customer events was scheduled.', $total))
            );
        }
    }

    /**
     * @param Context $context
     * @param string $parentJobId
     * @param array $channelIds
     *
     * @return void
     */
    private function processCartEvents(Context $context, string $parentJobId, array $channelIds): void
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

        if ($total > 0) {
            $this->jobResult->addMessage(
                new Message\InfoMessage(\sprintf('Total %s cart events was scheduled.', $total))
            );
        }
    }

    /**
     * @param Context $context
     * @param string $parentJobId
     * @param array $channelIds
     *
     * @return void
     */
    private function processOrderEvents(Context $context, string $parentJobId, array $channelIds): void
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

        if ($total > 0) {
            $this->jobResult->addMessage(
                new Message\InfoMessage(\sprintf('Total %s order events was scheduled.', $total))
            );
        }
    }

    /**
     * @param Context $context
     * @param string $parentJobId
     * @param array $channelIds
     * @param array $excludedEmailsMap
     *
     * @return void
     */
    private function processSubscriberEvents(
        Context $context,
        string $parentJobId,
        array $channelIds
    ): void
    {
        $total = 0;

        $iterator = $this->getEventRepoIterator($context, EventsTrackerInterface::SUBSCRIBER_EVENTS, $channelIds);

        while (($events = $iterator->fetch()) !== null) {
            $subscriberIds = $events->map(fn(EventEntity $event) => $event->getEntityId());
            $total += \count($subscriberIds);

            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(
                $subscriberIds,
                $parentJobId,
                $context,
                self::REALTIME_SUBSCRIBERS_OPERATION_LABEL
            );

            $this->deleteProcessedEvents($context, $events->getEntities());
        }

        if ($total > 0) {
            $this->jobResult->addMessage(
                new Message\InfoMessage(\sprintf('Total %s subscriber events was scheduled.', $total))
            );
        }
    }

    /**
     * @param Context $context
     * @param EntityCollection $events
     *
     * @return void
     */
    private function deleteProcessedEvents(Context $context, EntityCollection $events): void
    {
        $deleteDataSet = array_map(function ($id) {
            return ['id' => $id];
        }, array_values($events->getIds()));
        $this->eventRepository->delete($deleteDataSet, $context);
    }

    /**
     * @param Context $context
     * @param array $eventTypes
     * @param array $channelIds
     *
     * @return RepositoryIterator
     */
    private function getEventRepoIterator(Context $context, array $eventTypes, array $channelIds): RepositoryIterator
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
