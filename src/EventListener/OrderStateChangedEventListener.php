<?php

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\System\Tracking\Event\Order\OrderEvent;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderTrackingEventsBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStateChangedEventListener implements EventSubscriberInterface
{
    private EventsTrackerInterface $eventsTracker;
    private EntityRepositoryInterface $orderRepository;
    private EntityRepositoryInterface $orderTransactionRepository;

    public function __construct(
        EventsTrackerInterface $eventsTracker,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderTransactionRepository
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function onStateChange(StateMachineStateChangeEvent $event)
    {
        $supportedStates = [
            OrderStates::STATE_COMPLETED,
            OrderStates::STATE_CANCELLED,
            OrderTransactionStates::STATE_REFUNDED
        ];

        $state = $event->getNextState();
        if ($event->getTransitionSide() === StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            && $event->getTransition()->getEntityName() === OrderDefinition::ENTITY_NAME
            && in_array($state->getTechnicalName(), $supportedStates, true)
        ) {
            /** @var OrderEntity $order */
            $order = $this->orderRepository
                ->search(new Criteria([$event->getTransition()->getEntityId()]), $event->getContext())
                ->first();

            $this->trackEvent($event->getContext(), $order, $state->getTechnicalName());
        }
    }

    public function onTransactionStateChanged(StateMachineStateChangeEvent $event)
    {
        $supportedStates = [
            OrderTransactionStates::STATE_REFUNDED
        ];

        $state = $event->getNextState();
        if ($event->getTransitionSide() === StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            && $event->getTransition()->getEntityName() === OrderTransactionDefinition::ENTITY_NAME
            && in_array($state->getTechnicalName(), $supportedStates, true)
        ) {
            $orderTransactionCriteria = new Criteria([$event->getTransition()->getEntityId()]);
            $orderTransactionCriteria->addAssociation('order');
            /** @var OrderTransactionEntity $orderTransaction */
            $orderTransaction = $this->orderTransactionRepository
                ->search($orderTransactionCriteria, $event->getContext())
                ->first();

            $order = $orderTransaction->getOrder();

            $this->trackEvent($event->getContext(), $order, $state->getTechnicalName());
        }
    }

    private function trackEvent(Context $context, OrderEntity $order, string $state)
    {
        $eventsBag = new OrderTrackingEventsBag();
        $orderPlacedEvent = new OrderEvent($order);
        $eventsBag->add($orderPlacedEvent);

        switch ($state) {
            case OrderStates::STATE_COMPLETED:
                $this->eventsTracker->trackFulfilledOrders($context, $eventsBag);
                return;
            case OrderStates::STATE_CANCELLED:
                $this->eventsTracker->trackCanceledOrders($context, $eventsBag);
                return;
            case OrderTransactionStates::STATE_REFUNDED:
                $this->eventsTracker->trackRefundOrders($context, $eventsBag);
                return;
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'state_machine.order.state_changed' => 'onStateChange',
            'state_machine.order_transaction.state_changed' => 'onTransactionStateChanged'
        ];
    }
}
