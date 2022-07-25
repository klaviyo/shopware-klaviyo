<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\EventsProcessingMessage;
use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult, Message};
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\{EntityCollection, EntityRepositoryInterface};
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class EventsProcessingOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const HANDLER_CODE = 'od-klaviyo-events-sync-handler';

    private EntityRepositoryInterface $eventRepository;
    private EntityRepositoryInterface $cartEventRequestRepository;
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private GetValidChannels $getValidChannels;

    public function __construct(
        EntityRepositoryInterface $eventRepository,
        EntityRepositoryInterface $cartEventRequestRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        GetValidChannels $getValidChannels
    ) {
        $this->eventRepository = $eventRepository;
        $this->cartEventRequestRepository = $cartEventRequestRepository;
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
        $context = Context::createDefaultContext();
        $channelIds = $this->getValidChannels->execute()->map(fn(SalesChannelEntity $channel) => $channel->getId());
        $channelIds = \array_values($channelIds);

        if (empty($channelIds)) {
            $result->addMessage(new Message\WarningMessage('There are no configured channels - skipping.'));

            return $result;
        }

        $this->processOrderEvents($context, $message->getJobId(), $channelIds);
        $this->processCartEvents($context, $message->getJobId(), $channelIds);
        $this->processSubscriberEvents($context, $message->getJobId(), $channelIds);
        $this->processCustomerProfileEvents($context, $message->getJobId(), $channelIds);
        $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJobs($context, $message->getJobId(), $channelIds);

        return $result;
    }

    private function processCustomerProfileEvents(Context $context, string $parentJobId, array $channelIds)
    {
        $iterator = $this->getEventRepoIterator($context, [EventsTrackerInterface::CUSTOMER_WRITTEN_EVENT], $channelIds);

        while (($events = $iterator->fetch()) !== null) {
            $customerIds = $events->map(fn(EventEntity $event) => $event->getEntityId());
            $customerIds = array_values(array_unique($customerIds));
            $this->scheduleBackgroundJob->scheduleCustomerProfilesSyncJob($customerIds, $parentJobId);
            $this->deleteProcessedEvents($context, $events->getEntities());
        }
    }

    private function processCartEvents(Context $context, string $parentJobId, array $channelIds)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $channelIds));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);
        $iterator = new RepositoryIterator($this->cartEventRequestRepository, $context, $criteria);

        while (($eventRequestIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleCartEventsSyncJob($eventRequestIds, $parentJobId);
        }
    }

    private function processOrderEvents(Context $context, string $parentJobId, array $channelIds)
    {
        $iterator = $this->getEventRepoIterator($context, EventsTrackerInterface::ORDER_EVENTS, $channelIds);

        while (($eventIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleOrderEventsSyncJob($eventIds, $parentJobId);
        }
    }

    private function processSubscriberEvents(Context $context, string $parentJobId, array $channelIds)
    {
        $iterator = $this->getEventRepoIterator($context, EventsTrackerInterface::SUBSCRIBER_EVENTS, $channelIds);

        while (($events = $iterator->fetch()) !== null) {
            $subscriberIds = $events->map(fn(EventEntity $event) => $event->getEntityId());
            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(array_values($subscriberIds), $parentJobId);
            $this->deleteProcessedEvents($context, $events->getEntities());
        }
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
