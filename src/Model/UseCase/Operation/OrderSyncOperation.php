<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\System\OperationResult;
use Klaviyo\Integration\System\Tracking\Event\OrderEvent;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Klaviyo\Integration\System\Tracking\OrderTrackingEventsBag;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class OrderSyncOperation
{
    /**
     * @var string[]
     */
    private array $orderIds;
    private EntityRepositoryInterface $orderRepository;
    private EventsTrackerInterface $eventsTracker;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EventsTrackerInterface $eventsTracker
    ) {
        $this->orderRepository = $orderRepository;
        $this->eventsTracker = $eventsTracker;
    }

    public function setOrderIds(array $orderIds): void
    {
        $this->orderIds = $orderIds;
    }

    public function execute(Context $context): OperationResult
    {
        $eventsBag = new OrderTrackingEventsBag();
        $orderCriteria = new Criteria();
        $orderCriteria->addFilter(new EqualsAnyFilter('id', $this->orderIds));
        $orderCriteria->addAssociation('lineItems.product');
        $orderCriteria->addAssociation('orderCustomer.customer.defaultBillingAddress');
        $orderCriteria->addAssociation('orderCustomer.customer.defaultShippingAddress');
        $orderCollection = $this->orderRepository->search($orderCriteria, $context)->getEntities();

        foreach ($orderCollection as $order) {
            $eventsBag->add(new OrderEvent($order));
        }

        $this->eventsTracker->trackPlacedOrders($context, $eventsBag);

        return new OperationResult();
    }
}
