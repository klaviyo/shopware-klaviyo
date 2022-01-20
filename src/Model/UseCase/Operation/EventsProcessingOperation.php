<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\EventsProcessingMessage;
use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class EventsProcessingOperation implements JobHandlerInterface
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
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $context = Context::createDefaultContext();
        $this->processOrderEvents($context, $message->getJobId());
        $this->processCartEvents($context, $message->getJobId());
        $this->processSubscriberEvents($context, $message->getJobId());

        return new JobResult();
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
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('type', EventsTrackerInterface::ORDER_EVENTS));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);
        $iterator = new RepositoryIterator($this->eventRepository, $context, $criteria);

        while (($eventIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleOrderEventsSyncJob($eventIds, $parentJobId);
        }
    }

    private function processSubscriberEvents(Context $context, string $parentJobId)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('type', EventsTrackerInterface::SUBSCRIBER_EVENTS));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);
        $iterator = new RepositoryIterator($this->eventRepository, $context, $criteria);

        while (($events = $iterator->fetch()) !== null) {
            $subscriberIds = $events->map(fn(EventEntity $event) => $event->getEntityId());
            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(array_values($subscriberIds), $parentJobId);
            $deleteDataSet = array_map(function ($id) {
                return ['id' => $id];
            }, array_values($events->getIds()));
            $this->eventRepository->delete($deleteDataSet, $context);
        }
    }
}
