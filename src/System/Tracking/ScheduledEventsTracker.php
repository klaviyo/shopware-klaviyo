<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking;

use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\System\Tracking\Event\OrderEventInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;

class ScheduledEventsTracker implements EventsTrackerInterface
{
    private EntityRepositoryInterface $eventRepository;

    public function __construct(EntityRepositoryInterface $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function trackPlacedOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        // TODO: event type to constants
        return $this->trackEventsForBackgroundProcessing($context, $trackingBag, 'order-placed');
    }

    public function trackFulfilledOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackEventsForBackgroundProcessing($context, $trackingBag, 'order-fulfilled');
    }

    public function trackCanceledOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackEventsForBackgroundProcessing($context, $trackingBag, 'order-canceled');
    }

    public function trackRefundOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackEventsForBackgroundProcessing($context, $trackingBag, 'order-refunded');
    }

    private function trackEventsForBackgroundProcessing(
        Context $context,
        OrderTrackingEventsBag $trackingBag,
        string $eventType
    ): OrderTrackingResult {
        $scheduledEvents = [];
        $result = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            /** @var OrderEventInterface $event */
            foreach ($events as $event) {
                $scheduledEvents[] = [
                    'id' => Uuid::randomHex(),
                    'type' => $eventType,
                    'entityId' => $event->getOrder()->getId(),
                    'salesChannelId' => $channelId,
                    'happenedAt' => $event->getEventDateTime()
                ];
            }
        }

        try {
            $this->eventRepository->create($scheduledEvents, $context);
        } catch (\Throwable $e) {
            // TODO: add possibility to add simple (not order related) errors to OrderTrackingResult
            $result->addFailedOrder('0', $e);
        }

        return $result;
    }
}
