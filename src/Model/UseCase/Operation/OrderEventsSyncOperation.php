<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\OrderEventSyncMessage;
use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\Exception\JobRuntimeWarningException;
use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderEvent;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderTrackingEventsBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface as Tracker;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult, Message};
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class OrderEventsSyncOperation implements JobHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-order-event-sync-handler';
    private const ALLOWED_EVENT_TYPES = [
        Tracker::ORDER_EVENT_PLACED,
        Tracker::ORDER_EVENT_ORDERED_PRODUCT,
        Tracker::ORDER_EVENT_REFUNDED,
        Tracker::ORDER_EVENT_CANCELED,
        Tracker::ORDER_EVENT_FULFILLED,
        Tracker::ORDER_EVENT_PAID,
        Tracker::ORDER_EVENT_SHIPPED,
        Tracker::ORDER_EVENT_PARTIALLY_SHIPPED,
        Tracker::ORDER_EVENT_PARTIALLY_PAID,
    ];

    private EntityRepository $eventsRepository;
    private EntityRepository $orderRepository;
    private Tracker $eventsTracker;

    public function __construct(
        EntityRepository $eventsRepository,
        EntityRepository $orderRepository,
        Tracker $eventsTracker
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
        $result->addMessage(new Message\InfoMessage('Starting Order Events Sync Operation...'));
        $context = $message->getContext();

        foreach (self::ALLOWED_EVENT_TYPES as $eventType) {
            $eventCriteria = new Criteria();
            $eventCriteria->addFilter(new EqualsFilter('type', $eventType));
            $eventCriteria->addFilter(new EqualsAnyFilter('id', $message->getEventIds()));
            $events = $this->eventsRepository->search($eventCriteria, $context)->getElements();

            $eventTypeName = Tracker::ORDER_EVENTS[$eventType] ?? 'Undefined type';
            $eventsBag = new OrderTrackingEventsBag();
            $eventTypeOrderIds = array_map(fn(EventEntity $event) => $event->getEntityId(), $events);
            $orderCriteria = new Criteria();
            $orderCriteria->addFilter(new EqualsAnyFilter('id', $eventTypeOrderIds));
            $orderCriteria->addAssociation('lineItems');
            $orderCriteria->addAssociation('orderCustomer.customer.defaultBillingAddress');
            $orderCriteria->addAssociation('orderCustomer.customer.defaultShippingAddress');
            $orders = $this->orderRepository->search($orderCriteria, $context)->getEntities()->getElements();

            $result->addMessage(new Message\InfoMessage(
                \sprintf('Total %s "%s" order events to process.', \count($orders), $eventTypeName))
            );

            /** @var EventEntity $deferredEvent */
            foreach ($events as $deferredEvent) {
                if (isset($orders[$deferredEvent->getEntityId()])) {
                    $orderEvent = new OrderEvent(
                        $orders[$deferredEvent->getEntityId()],
                        $deferredEvent->getHappenedAt()
                    );
                    $eventsBag->add($orderEvent);
                }
            }

            switch ($eventType) {
                case Tracker::ORDER_EVENT_PLACED:
                    $trackingResult = $this->eventsTracker->trackPlacedOrders($context, $eventsBag);
                    break;
                case Tracker::ORDER_EVENT_ORDERED_PRODUCT:
                    $trackingResult = $this->eventsTracker->trackOrderedProducts($context, $eventsBag);
                    break;
                case Tracker::ORDER_EVENT_CANCELED:
                    $trackingResult = $this->eventsTracker->trackCanceledOrders($context, $eventsBag);
                    break;
                case Tracker::ORDER_EVENT_REFUNDED:
                    $trackingResult = $this->eventsTracker->trackRefundOrders($context, $eventsBag);
                    break;
                case Tracker::ORDER_EVENT_FULFILLED:
                    $trackingResult = $this->eventsTracker->trackFulfilledOrders($context, $eventsBag);
                    break;
                case Tracker::ORDER_EVENT_PAID:
                    $trackingResult = $this->eventsTracker->trackPaiedOrders($context, $eventsBag);
                    break;
                case Tracker::ORDER_EVENT_SHIPPED:
                    $trackingResult = $this->eventsTracker->trackShippedOrder($context, $eventsBag);
                    break;
                case Tracker::ORDER_EVENT_PARTIALLY_PAID:
                    $trackingResult = $this->eventsTracker->trackPartiallyPaidOrders($context, $eventsBag);
                    break;
                case Tracker::ORDER_EVENT_PARTIALLY_SHIPPED:
                    $trackingResult = $this->eventsTracker->trackPartiallyShippedOrder($context, $eventsBag);
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
                    if ($error instanceof JobRuntimeWarningException) {
                        $result->addMessage(new Message\WarningMessage($error->getMessage()));
                    } else {
                        $result->addMessage(new Message\ErrorMessage(
                            \sprintf('Order[id: %s] error: %s', $orderId, $error->getMessage())
                        ));
                    }
                }
            }
        }

        $result->addMessage(new Message\InfoMessage('Operation finished.'));

        return $result;
    }
}
