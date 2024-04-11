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

    /**
     * @param EntityRepository $eventsRepository
     * @param EntityRepository $orderRepository
     * @param Tracker $eventsTracker
     */
    public function __construct(
        private readonly EntityRepository $eventsRepository,
        private readonly EntityRepository $orderRepository,
        private readonly Tracker $eventsTracker
    ) {
    }

    /**
     * @param OrderEventSyncMessage $message
     *
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $messages = [];
        $result = new JobResult();
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

            if (count($orders) > 0) {
                $messages[] = new Message\InfoMessage(
                    \sprintf('Total %s "%s" order events to process.', \count($orders), $eventTypeName)
                );
            }

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

            $trackingResult = match ($eventType) {
                Tracker::ORDER_EVENT_PLACED => $this->eventsTracker->trackPlacedOrders($context, $eventsBag),
                Tracker::ORDER_EVENT_ORDERED_PRODUCT => $this->eventsTracker->trackOrderedProducts(
                    $context,
                    $eventsBag
                ),
                Tracker::ORDER_EVENT_CANCELED => $this->eventsTracker->trackCanceledOrders($context, $eventsBag),
                Tracker::ORDER_EVENT_REFUNDED => $this->eventsTracker->trackRefundOrders($context, $eventsBag),
                Tracker::ORDER_EVENT_FULFILLED => $this->eventsTracker->trackFulfilledOrders($context, $eventsBag),
                Tracker::ORDER_EVENT_PAID => $this->eventsTracker->trackPaiedOrders($context, $eventsBag),
                Tracker::ORDER_EVENT_SHIPPED => $this->eventsTracker->trackShippedOrder($context, $eventsBag),
                Tracker::ORDER_EVENT_PARTIALLY_PAID => $this->eventsTracker->trackPartiallyPaidOrders(
                    $context,
                    $eventsBag
                ),
                Tracker::ORDER_EVENT_PARTIALLY_SHIPPED => $this->eventsTracker->trackPartiallyShippedOrder(
                    $context,
                    $eventsBag
                ),
                default => new OrderTrackingResult(),
            };

            $deleteDataSet = array_map(
                function (EventEntity $event) {
                    return ['id' => $event->getId()];
                },
                array_values($events)
            );
            $this->eventsRepository->delete($deleteDataSet, $context);

            foreach ($trackingResult->getFailedOrdersErrors() as $orderId => $orderErrors) {
                /** @var \Throwable $error */
                foreach ($orderErrors as $error) {
                    if ($error instanceof JobRuntimeWarningException) {
                        $messages[] = new Message\WarningMessage($error->getMessage());
                    } else {
                        $messages[] = new Message\ErrorMessage(
                            \sprintf('Order[id: %s] error: %s', $orderId, $error->getMessage())
                        );
                    }
                }
            }
        }

        if (count($messages)) {
            $result->addMessage(new Message\InfoMessage('Starting Order Events Sync Operation...'));
            array_walk($messages, fn($message) => $result->addMessage($message));
            $result->addMessage(new Message\InfoMessage('Operation finished.'));
        }
        return $result;
    }
}
