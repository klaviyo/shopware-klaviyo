<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\System\Tracking\Event\Order\{OrderEvent, OrderTrackingEventsBag};
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Checkout\Order\{OrderDefinition, OrderEntity, OrderStates};
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\{EntityRepository, Search\Criteria};
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStateChangedEventListener implements EventSubscriberInterface
{
    private EventsTrackerInterface $eventsTracker;
    private EntityRepository $orderRepository;
    private EntityRepository $orderTransactionRepository;
    private GetValidChannelConfig $getValidChannelConfig;
    private EntityRepository $orderDeliveryRepository;

    public function __construct(
        EventsTrackerInterface $eventsTracker,
        EntityRepository $orderRepository,
        EntityRepository $orderTransactionRepository,
        GetValidChannelConfig $getValidChannelConfig,
        EntityRepository $orderDeliveryRepository
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->getValidChannelConfig = $getValidChannelConfig;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_machine.order.state_changed' => 'onStateChange',
            'state_machine.order_delivery.state_changed' => 'onDeliveryStateChanged',
            'state_machine.order_transaction.state_changed' => 'onTransactionStateChanged'
        ];
    }

    public function onStateChange(StateMachineStateChangeEvent $event)
    {
        /** @var OrderEntity $order */
        $order = $this->orderRepository
            ->search(new Criteria([$event->getTransition()->getEntityId()]), $event->getContext())
            ->first();
        if (!($order instanceof OrderEntity)) {
            return;
        }

        $configuration = $this->getValidChannelConfig->execute($order->getSalesChannelId());

        if ($configuration === null) {
            return;
        }

        $supportedStates = [
            OrderStates::STATE_COMPLETED => $configuration->isTrackFulfilledOrder(),
            OrderStates::STATE_CANCELLED => $configuration->isTrackCanceledOrder(),
            OrderTransactionStates::STATE_REFUNDED => $configuration->isTrackRefundedOrder(),
            OrderTransactionStates::STATE_PAID => $configuration->isTrackPaidOrder(),
            OrderTransactionStates::STATE_PARTIALLY_PAID => $configuration->isTrackPaidOrder(),
            OrderDeliveryStates::STATE_SHIPPED => $configuration->isTrackShippedOrder(),
            OrderDeliveryStates::STATE_PARTIALLY_SHIPPED => $configuration->isTrackShippedOrder(),
        ];

        $state = $event->getNextState();
        if ($event->getTransitionSide() === StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            && $event->getTransition()->getEntityName() === OrderDefinition::ENTITY_NAME
            && !empty($supportedStates[$state->getTechnicalName()])
        ) {
            $this->trackEvent($event->getContext(), $order, $state->getTechnicalName());
        }
    }

    public function onTransactionStateChanged(StateMachineStateChangeEvent $event): void
    {
        $orderTransactionCriteria = new Criteria([$event->getTransition()->getEntityId()]);
        $orderTransactionCriteria->addAssociation('order');
        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository
            ->search($orderTransactionCriteria, $event->getContext())
            ->first();
        if(!($orderTransaction instanceof OrderTransactionEntity)) {
            return;
        }

        $order = $orderTransaction->getOrder();
        if (!($order instanceof OrderEntity)) {
            return;
        }

        $configuration = $this->getValidChannelConfig->execute($order->getSalesChannelId());
        if ($configuration === null) {
            return;
        }

        $state = $event->getNextState();
        if ($event->getTransitionSide() === StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            && $event->getTransition()->getEntityName() === OrderTransactionDefinition::ENTITY_NAME
            && (
                ($state->getTechnicalName() === OrderTransactionStates::STATE_REFUNDED && $configuration->isTrackRefundedOrder()) ||
                ($state->getTechnicalName() === OrderTransactionStates::STATE_PAID && $configuration->isTrackPaidOrder()) ||
                ($state->getTechnicalName() === OrderTransactionStates::STATE_PARTIALLY_PAID && $configuration->isTrackPaidOrder())
            )
        ) {
            $this->trackEvent($event->getContext(), $order, $state->getTechnicalName());
        }
    }

    public function onDeliveryStateChanged(StateMachineStateChangeEvent $event): void
    {
        $orderDeliveryCriteria = new Criteria([$event->getTransition()->getEntityId()]);
        $orderDeliveryCriteria->addAssociation('order');

        /** @var OrderDeliveryEntity $orderDelivery */
        $orderDelivery = $this->orderDeliveryRepository
            ->search($orderDeliveryCriteria, $event->getContext())
            ->first();

        if(!($orderDelivery instanceof OrderDeliveryEntity)) {
            return;
        }

        $order = $orderDelivery->getOrder();

        if (!($order instanceof OrderEntity)) {
            return;
        }

        $configuration = $this->getValidChannelConfig->execute($order->getSalesChannelId());

        if ($configuration === null) {
            return;
        }

        $state = $event->getNextState();

        if ($event->getTransitionSide() === StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            && $event->getTransition()->getEntityName() === OrderDeliveryDefinition::ENTITY_NAME
            && ($state->getTechnicalName() === OrderDeliveryStates::STATE_SHIPPED && $configuration->isTrackShippedOrder()) ||
            ($state->getTechnicalName() === OrderDeliveryStates::STATE_PARTIALLY_SHIPPED && $configuration->isTrackShippedOrder())
        ) {
            $this->trackEvent($event->getContext(), $order, $state->getTechnicalName());
        }
    }

    private function trackEvent(Context $context, OrderEntity $order, string $state): void
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
            case OrderTransactionStates::STATE_PAID:
                $this->eventsTracker->trackPaiedOrders($context, $eventsBag);
                return;
            case OrderTransactionStates::STATE_PARTIALLY_PAID:
                $this->eventsTracker->trackPartiallyPaidOrders($context, $eventsBag);
                return;
            case OrderDeliveryStates::STATE_SHIPPED:
                $this->eventsTracker->trackShippedOrder($context, $eventsBag);
                return;
            case OrderDeliveryStates::STATE_PARTIALLY_SHIPPED:
                $this->eventsTracker->trackPartiallyShippedOrder($context, $eventsBag);
                return;
        }
    }
}
