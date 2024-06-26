<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\OrderSyncMessage;
use Klaviyo\Integration\Exception\JobRuntimeWarningException;
use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\System\Tracking\Event\Order\{OrderEvent, OrderTrackingEventsBag};
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface as Tracker;
use Od\Scheduler\Model\Job\{JobHandlerInterface, JobResult, Message};
use Shopware\Core\Checkout\Order\{Aggregate\OrderDelivery\OrderDeliveryStates, OrderEntity, OrderStates};
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions as StateActions;

class OrderSyncOperation implements JobHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-order-sync-handler';

    private EntityRepositoryInterface $orderRepository;
    private Tracker $eventsTracker;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        Tracker $eventsTracker
    ) {
        $this->orderRepository = $orderRepository;
        $this->eventsTracker = $eventsTracker;
    }

    /**
     * @param OrderSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $result->addMessage(new Message\InfoMessage('Starting Order Sync Operation...'));
        $eventsBags = [
            Tracker::ORDER_EVENT_PLACED => new OrderTrackingEventsBag(),
            Tracker::ORDER_EVENT_ORDERED_PRODUCT => new OrderTrackingEventsBag(),
            Tracker::ORDER_EVENT_REFUNDED => new OrderTrackingEventsBag(),
            Tracker::ORDER_EVENT_CANCELED => new OrderTrackingEventsBag(),
            Tracker::ORDER_EVENT_FULFILLED => new OrderTrackingEventsBag(),
            Tracker::ORDER_EVENT_PAID => new OrderTrackingEventsBag(),
            Tracker::ORDER_EVENT_SHIPPED => new OrderTrackingEventsBag(),
            Tracker::ORDER_EVENT_PARTIALLY_SHIPPED => new OrderTrackingEventsBag(),
            Tracker::ORDER_EVENT_PARTIALLY_PAID => new OrderTrackingEventsBag(),
        ];

        $orderCriteria = new Criteria();
        $orderCriteria->addFilter(new EqualsAnyFilter('id', $message->getOrderIds()));
        $orderCriteria->addAssociation('stateMachineState');
        $orderCriteria->addAssociation('lineItems.product');
        $orderCriteria->addAssociation('orderCustomer.customer.defaultBillingAddress');
        $orderCriteria->addAssociation('orderCustomer.customer.defaultShippingAddress');
        $orderCriteria->addAssociation('deliveries');
        $orderCriteria->addAssociation('transactions');

        $orderCollection = $this->orderRepository->search($orderCriteria, $message->getContext());



        /** @var OrderEntity $order */
        foreach ($orderCollection as $order) {

            $eventsBags[Tracker::ORDER_EVENT_PLACED]->add(new OrderEvent($order, $order->getCreatedAt()));
            $eventsBags[Tracker::ORDER_EVENT_ORDERED_PRODUCT]->add(new OrderEvent($order, $order->getCreatedAt()));

            $lastTransaction = $order->getTransactions()->last();
            $transactionStateName = null;

            if ($lastTransaction !== null) {
                $transactionStateName = $lastTransaction->getStateMachineState()->getTechnicalName() ?: null;
            }

            if (StateActions::ACTION_PAID === $transactionStateName) {
                $happenedAt = $lastTransaction->getUpdatedAt();
                $eventsBags[Tracker::ORDER_EVENT_PAID]->add(new OrderEvent($order, $happenedAt));
            }

            if (StateActions::ACTION_PAID_PARTIALLY === $transactionStateName) {
                $happenedAt = $lastTransaction->getUpdatedAt();
                $eventsBags[Tracker::ORDER_EVENT_PARTIALLY_PAID]->add(new OrderEvent($order, $happenedAt));
            }

            $lastDelivery = $order->getDeliveries()->last();
            $deliveryStateName = null;

            if ($lastDelivery !== null) {
                $deliveryStateName = $lastDelivery->getStateMachineState()->getTechnicalName() ?: null;
            }

            $orderStateName = $order->getStateMachineState()->getTechnicalName();

            if ($deliveryStateName === OrderDeliveryStates::STATE_SHIPPED)
            {
                $happenedAt = $lastDelivery->getUpdatedAt();
                $eventsBags[Tracker::ORDER_EVENT_SHIPPED]->add(new OrderEvent($order, $happenedAt));
            }

            if (OrderDeliveryStates::STATE_PARTIALLY_SHIPPED === $deliveryStateName) {
                $happenedAt = $lastDelivery->getUpdatedAt();
                $eventsBags[Tracker::ORDER_EVENT_PARTIALLY_SHIPPED]->add(new OrderEvent($order, $happenedAt));
            }

            if (OrderStates::STATE_COMPLETED === $orderStateName) {
                $happenedAt = $order->getUpdatedAt();
                $eventsBags[Tracker::ORDER_EVENT_FULFILLED]->add(new OrderEvent($order, $happenedAt));
            }

            if ($orderStateName === OrderStates::STATE_CANCELLED) {
                $happenedAt = $order->getUpdatedAt();
                $eventsBags[Tracker::ORDER_EVENT_CANCELED]->add(new OrderEvent($order, $happenedAt));
            }

            if ($transactionStateName === OrderTransactionStates::STATE_REFUNDED) {
                $happenedAt = $lastTransaction->getUpdatedAt();
                $eventsBags[Tracker::ORDER_EVENT_REFUNDED]->add(new OrderEvent($order, $happenedAt));
            }

        }


        if ($orderCollection->count() !== 0) {
            $result->addMessage(new Message\InfoMessage('Start sending tracking requests...'));
        }

        foreach ($eventsBags as $type => $eventsBag) {
            $trackingResult = $this->trackEventBagByType($message->getContext(), $eventsBag, $type);

            foreach ($trackingResult->getFailedOrdersErrors() as $orderId => $orderErrors) {
                /** @var \Throwable $error */
                foreach ($orderErrors as $error) {
                    $eventTypeName = Tracker::ORDER_EVENTS[$type] ?? 'Undefined type';

                    if ($error instanceof JobRuntimeWarningException) {
                        $result->addMessage(new Message\WarningMessage(
                            \sprintf('[%s] %s', $eventTypeName, $error->getMessage())
                        ));
                    } else {
                        $result->addMessage(new Message\ErrorMessage(
                            \sprintf('[%s] Order[id: %s] error: %s', $eventTypeName, $orderId, $error->getMessage())
                        ));
                    }
                }
            }
        }

        $result->addMessage(new Message\InfoMessage('Operation finished.'));

        return $result;
    }

    private function trackEventBagByType(
        Context $context,
        OrderTrackingEventsBag $eventsBag,
        string $type
    ): OrderTrackingResult {
        switch ($type) {
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
            case Tracker::ORDER_EVENT_PARTIALLY_SHIPPED:
                $trackingResult = $this->eventsTracker->trackPartiallyShippedOrder($context, $eventsBag);
                break;
            case Tracker::ORDER_EVENT_PARTIALLY_PAID:
                $trackingResult = $this->eventsTracker->trackPartiallyPaidOrders($context, $eventsBag);
                break;
            default:
                $trackingResult = new OrderTrackingResult();
                break;
        }

        return $trackingResult;
    }
}
