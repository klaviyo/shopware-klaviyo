<?php

namespace Klaviyo\Integration\Tracking\Job;

use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\AbstractJobProcessor;
use Klaviyo\Integration\Tracking\EventsTracker;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class HistoricalEventsTrackingJobProcessor extends AbstractJobProcessor
{
    public const DEFAULT_ORDER_EXPORT_CHUNK_SIZE = 1000;

    private EventsTracker $eventsTracker;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $orderRepository;
    private int $orderExportChunkSize;

    public function __construct(
        EventsTracker $eventsTracker,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $orderRepository,
        JobHelper $jobHelper,
        $orderExportChunkSize = self::DEFAULT_ORDER_EXPORT_CHUNK_SIZE
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->orderRepository = $orderRepository;
        $this->orderExportChunkSize = $orderExportChunkSize;

        parent::__construct($jobHelper);
    }

    protected function doProcess(Context $context, JobEntity $jobEntity): bool
    {
        $channels = $this->salesChannelRepository->search(new Criteria(), $context);

        $success = true;
        /** @var SalesChannelEntity $channel */
        foreach ($channels as $channel) {
            if (!$this->processSalesChannelOrders($context, $channel)) {
                $success = false;
            }
        }

        return $success;
    }

    protected function processSalesChannelOrders(Context $context, SalesChannelEntity $salesChannelEntity): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelEntity->getId()));

        $success = true;

        $ordersIds = $this->orderRepository->searchIds($criteria, $context)->getIds();
        // Use butches to avoid memory limit and performance issues
        $orderIdChunks = array_chunk($ordersIds, $this->orderExportChunkSize);
        foreach ($orderIdChunks as $orderIds) {
            $orderCriteria = new Criteria($orderIds);
            $orderCriteria->addAssociation('orderCustomer.customer.defaultBillingAddress');
            $orderCriteria->addAssociation('orderCustomer.customer.defaultShippingAddress');
            $orders = $this->orderRepository->search($orderCriteria, $context);
            $orders->getElements();
            /** @var OrderEntity $order */
            foreach ($orders as $order) {
                if (!$this->eventsTracker->trackPlacedOrder($context, $salesChannelEntity, $order)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    public function isApplicable(Context $context, JobEntity $job): bool
    {
        return $job->getType() === JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE;
    }
}
