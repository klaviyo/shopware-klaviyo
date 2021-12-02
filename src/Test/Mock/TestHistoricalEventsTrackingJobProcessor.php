<?php

namespace Klaviyo\Integration\Test\Mock;

use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Tracking\EventsTracker;
use Klaviyo\Integration\Tracking\Job\HistoricalEventsTrackingJobProcessor;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class TestHistoricalEventsTrackingJobProcessor extends HistoricalEventsTrackingJobProcessor
{
    public function __construct(
        EventsTracker $eventsTracker,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $orderRepository,
        JobHelper $jobHelper
    ) {
        parent::__construct(
            $eventsTracker,
            $salesChannelRepository,
            $orderRepository,
            $jobHelper,
            2
        );
    }
}