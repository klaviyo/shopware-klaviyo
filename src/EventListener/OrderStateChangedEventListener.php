<?php

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Tracking\EventsTracker;
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
    private EventsTracker $eventsTracker;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $orderRepository;
    private EntityRepositoryInterface $orderTransactionRepository;

    public function __construct(
        EventsTracker $eventsTracker,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderTransactionRepository
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->salesChannelRepository = $salesChannelRepository;
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
            && in_array($state->getTechnicalName(), $supportedStates, true)) {

            /** @var OrderEntity $order */
            $order = $this->orderRepository
                ->search(new Criteria([$event->getTransition()->getEntityId()]), $event->getContext())
                ->first();

            $salesChannel = $this->salesChannelRepository
                ->search(new Criteria([$order->getSalesChannelId()]), $event->getContext())
                ->first();

            $this->trackEvent($event->getContext(), $salesChannel, $order, $state->getTechnicalName());
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
            && in_array($state->getTechnicalName(), $supportedStates, true)) {

            $orderTransactionCriteria = new Criteria([$event->getTransition()->getEntityId()]);
            $orderTransactionCriteria->addAssociation('order');
            /** @var OrderTransactionEntity $orderTransaction */
            $orderTransaction = $this->orderTransactionRepository
                ->search($orderTransactionCriteria, $event->getContext())
                ->first();

            $order = $orderTransaction->getOrder();

            $salesChannel = $this->salesChannelRepository
                ->search(new Criteria([$order->getSalesChannelId()]), $event->getContext())
                ->first();

            $this->trackEvent($event->getContext(), $salesChannel, $order, $state->getTechnicalName());
        }
    }

    private function trackEvent(Context $context, SalesChannelEntity $salesChannel, OrderEntity $order, string $state)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        switch ($state) {
            case OrderStates::STATE_COMPLETED:
                $this->eventsTracker->trackFulfilledOrder(
                    $context,
                    $salesChannel,
                    $order,
                    $now
                );
                return;
            case OrderStates::STATE_CANCELLED:
                $this->eventsTracker->trackCanceledOrder(
                    $context,
                    $salesChannel,
                    $order,
                    $now
                );
                return;
            case OrderTransactionStates::STATE_REFUNDED:
                $this->eventsTracker->trackRefundOrder(
                    $context,
                    $salesChannel,
                    $order,
                    $now
                );
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