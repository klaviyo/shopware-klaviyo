<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\EventsProcessingMessage;
use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\{JobResult, JobHandlerInterface};
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\{EntityCollection, EntityRepositoryInterface};
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class EventsProcessingOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const HANDLER_CODE = 'od-klaviyo-events-sync-handler';

    private EntityRepositoryInterface $eventRepository;
    private EntityRepositoryInterface $cartEventRequestRepository;
    private ScheduleBackgroundJob $scheduleBackgroundJob;

    public function __construct(
        EntityRepositoryInterface $eventRepository,
        EntityRepositoryInterface $cartEventRequestRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob
    ) {
        $this->eventRepository = $eventRepository;
        $this->cartEventRequestRepository = $cartEventRequestRepository;
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
    }

    /**
     * @param EventsProcessingMessage $message
     *
     * @return JobResult
     * @throws \Exception
     */
    public function execute(object $message): JobResult
    {
        $context = Context::createDefaultContext();
        $this->processOrderEvents($context, $message->getJobId());
        $this->processCartEvents($context, $message->getJobId());
        $this->processSubscriberEvents($context, $message->getJobId());
        $this->processCustomerProfileEvents($context, $message->getJobId());
        $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJobs($context, $message->getJobId());

        return new JobResult();
    }

    private function processCustomerProfileEvents(Context $context, string $parentJobId)
    {
        $iterator = $this->getEventRepoIterator($context, [EventsTrackerInterface::CUSTOMER_WRITTEN_EVENT]);

        while (($events = $iterator->fetch()) !== null) {
            $customerIds = $events->map(fn(EventEntity $event) => $event->getEntityId());
            $customerIds = array_values(array_unique($customerIds));
            $this->scheduleBackgroundJob->scheduleCustomerProfilesSyncJob($customerIds, $parentJobId);
            $this->deleteProcessedEvents($context, $events->getEntities());
        }
    }

    private function processCartEvents(Context $context, string $parentJobId)
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);
        $iterator = new RepositoryIterator($this->cartEventRequestRepository, $context, $criteria);

        while (($eventRequestIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleCartEventsSyncJob($eventRequestIds, $parentJobId);
        }
    }

    private function processOrderEvents(Context $context, string $parentJobId)
    {
        $iterator = $this->getEventRepoIterator($context, EventsTrackerInterface::ORDER_EVENTS);

        while (($eventIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleOrderEventsSyncJob($eventIds, $parentJobId);
        }
    }

    private function processSubscriberEvents(Context $context, string $parentJobId)
    {
        $iterator = $this->getEventRepoIterator($context, EventsTrackerInterface::SUBSCRIBER_EVENTS);

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

    private function getEventRepoIterator(Context $context, array $eventTypes)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('type', $eventTypes));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);

        return new RepositoryIterator($this->eventRepository, $context, $criteria);
    }
}
