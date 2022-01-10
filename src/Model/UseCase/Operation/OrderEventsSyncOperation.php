<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\System\OperationResult;
use Klaviyo\Integration\System\Tracking\Event\OrderEvent;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Klaviyo\Integration\System\Tracking\OrderTrackingEventsBag;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class OrderEventsSyncOperation
{
    private const ALLOWED_EVENT_TYPES = [
        EventEntity::TYPE_ORDER_FULFILLED,
        EventEntity::TYPE_ORDER_CANCELED,
        EventEntity::TYPE_ORDER_REFUNDED,
    ];

    private array $eventIds = [];
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

    public function setEventIds(array $eventIds)
    {
        $this->eventIds = $eventIds;
    }

    public function execute(Context $context): OperationResult
    {
        foreach (self::ALLOWED_EVENT_TYPES as $eventType) {
            $eventCriteria = new Criteria();
            $eventCriteria->addFilter(new EqualsFilter('type', $eventType));
            $eventCriteria->addFilter(new EqualsAnyFilter('id', $this->eventIds));
            $events = $this->eventsRepository->search($eventCriteria, $context);

            $eventsBag = new OrderTrackingEventsBag();
            $eventTypeOrderIds = array_map(fn(EventEntity $event) => $event->getEntityId(), $events->getElements());
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
                case EventEntity::TYPE_ORDER_FULFILLED:
                    $this->eventsTracker->trackFulfilledOrders($context, $eventsBag);
                    break;
                case EventEntity::TYPE_ORDER_CANCELED:
                    $this->eventsTracker->trackCanceledOrders($context, $eventsBag);
                    break;
                case EventEntity::TYPE_ORDER_REFUNDED:
                    $this->eventsTracker->trackRefundOrders($context, $eventsBag);
                    break;
            }
        }

        return new OperationResult();
    }
}
