<?php declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\System\Tracking\Event\Order\{OrderEvent, OrderTrackingEventsBag};
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Checkout\Order\{OrderDefinition, OrderEntity, OrderStates};
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\{EntityRepositoryInterface, Search\Criteria};
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStateChangedEventListener implements EventSubscriberInterface
{
    private EventsTrackerInterface $eventsTracker;
    private EntityRepositoryInterface $orderRepository;
    private EntityRepositoryInterface $orderTransactionRepository;
    private GetValidChannelConfig $getValidChannelConfig;

    public function __construct(
        EventsTrackerInterface $eventsTracker,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderTransactionRepository,
        GetValidChannelConfig $getValidChannelConfig
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->getValidChannelConfig = $getValidChannelConfig;
    }

    public static function getSubscribedEvents()
    {
        return [
            'state_machine.order.state_changed' => 'onStateChange',
            'state_machine.order_transaction.state_changed' => 'onTransactionStateChanged'
        ];
    }

    public function onStateChange(StateMachineStateChangeEvent $event)
    {
        $configuration = $this->getValidChannelConfig->execute($event->getSalesChannelId());
        if ($configuration === null) {
            return;
        }

        $supportedStates = [
            OrderStates::STATE_COMPLETED => $configuration->isTrackFulfilledOrder(),
            OrderStates::STATE_CANCELLED => $configuration->isTrackCanceledOrder(),
            OrderTransactionStates::STATE_REFUNDED => $configuration->isTrackRefundedOrder()
        ];

        $state = $event->getNextState();
        if ($event->getTransitionSide() === StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            && $event->getTransition()->getEntityName() === OrderDefinition::ENTITY_NAME
            && !empty($supportedStates[$state->getTechnicalName()])
        ) {
            /** @var OrderEntity $order */
            $order = $this->orderRepository
                ->search(new Criteria([$event->getTransition()->getEntityId()]), $event->getContext())
                ->first();

            $this->trackEvent($event->getContext(), $order, $state->getTechnicalName());
        }
    }

    // TODO: check do we actually need this method?
    public function onTransactionStateChanged(StateMachineStateChangeEvent $event)
    {
        $configuration = $this->getValidChannelConfig->execute($event->getSalesChannelId());
        if ($configuration === null || !$configuration->isTrackRefundedOrder()) {
            return;
        }

        $state = $event->getNextState();
        if ($event->getTransitionSide() === StateMachineStateChangeEvent::STATE_MACHINE_TRANSITION_SIDE_ENTER
            && $event->getTransition()->getEntityName() === OrderTransactionDefinition::ENTITY_NAME
            && $state->getTechnicalName() === OrderTransactionStates::STATE_REFUNDED
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
}
