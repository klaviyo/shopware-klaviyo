<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\OrderEventSyncMessage;
use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderEvent;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderTrackingEventsBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class OrderEventsSyncOperation implements JobHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-order-event-sync-handler';
    private const ALLOWED_EVENT_TYPES = [
        EventsTrackerInterface::ORDER_EVENT_PLACED,
        EventsTrackerInterface::ORDER_EVENT_REFUNDED,
        EventsTrackerInterface::ORDER_EVENT_CANCELED,
        EventsTrackerInterface::ORDER_EVENT_FULFILLED,
    ];

    private EntityRepositoryInterface $eventsRepository;
    private EntityRepositoryInterface $orderRepository;
    private EventsTrackerInterface $eventsTracker;

    public function __construct(
        EntityRepositoryInterface $eventsRepository,
        EntityRepositoryInterface $orderRepository,
        EventsTrackerInterface $eventsTracker
    ) {
        $this->eventsRepository = $eventsRepository;
        $this->orderRepository = $orderRepository;
        $this->eventsTracker = $eventsTracker;
    }

    /**
     * @param OrderEventSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $context = Context::createDefaultContext();

        foreach (self::ALLOWED_EVENT_TYPES as $eventType) {
            $eventCriteria = new Criteria();
            $eventCriteria->addFilter(new EqualsFilter('type', $eventType));
            $eventCriteria->addFilter(new EqualsAnyFilter('id', $message->getEventIds()));
            $events = $this->eventsRepository->search($eventCriteria, $context)->getElements();

            $eventsBag = new OrderTrackingEventsBag();
            $eventTypeOrderIds = array_map(fn(EventEntity $event) => $event->getEntityId(), $events);
            $orderCriteria = new Criteria();
            $orderCriteria->addFilter(new EqualsAnyFilter('id', $eventTypeOrderIds));
            $orderCriteria->addAssociation('orderCustomer.customer.defaultBillingAddress');
            $orderCriteria->addAssociation('orderCustomer.customer.defaultShippingAddress');
            $orders = $this->orderRepository->search($orderCriteria, $context)->getEntities()->getElements();

            /** @var EventEntity $deferredEvent */
            foreach ($events as $deferredEvent) {
                if ($order = $orders[$deferredEvent->getEntityId()]) {
                    $orderEvent = new OrderEvent($order, $deferredEvent->getHappenedAt());
                    $eventsBag->add($orderEvent);
                }
            }

            switch ($eventType) {
                case EventsTrackerInterface::ORDER_EVENT_PLACED:
                    $trackingResult = $this->eventsTracker->trackPlacedOrders($context, $eventsBag);
                    break;
                case EventsTrackerInterface::ORDER_EVENT_CANCELED:
                    $trackingResult = $this->eventsTracker->trackCanceledOrders($context, $eventsBag);
                    break;
                case EventsTrackerInterface::ORDER_EVENT_REFUNDED:
                    $trackingResult = $this->eventsTracker->trackRefundOrders($context, $eventsBag);
                    break;
                case EventsTrackerInterface::ORDER_EVENT_FULFILLED:
                    $trackingResult = $this->eventsTracker->trackFulfilledOrders($context, $eventsBag);
                    break;
                default:
                    $trackingResult = new OrderTrackingResult();
                    break;
            }

            $deleteDataSet = array_map(function (EventEntity $event) {
                return ['id' => $event->getId()];
                }, array_values($events));
            $this->eventsRepository->delete($deleteDataSet, $context);

            foreach ($trackingResult->getFailedOrdersErrors() as $orderId => $orderErrors) {
                /** @var \Throwable $error */
                foreach ($orderErrors as $error) {
                    $result->addError(new \Exception(
                        \sprintf('Order[id: %s] error: %s', $orderId, $error->getMessage())
                    ));
                }
            }
        }

        return $result;
    }
}
