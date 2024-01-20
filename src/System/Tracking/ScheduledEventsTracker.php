<?php

declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking;

use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\Model\CartRequestSerializer;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\Event\Customer\ProfileEventsBag;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderTrackingEventsBag;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Psr\Log\LoggerInterface;

class ScheduledEventsTracker implements EventsTrackerInterface
{
    private EntityRepositoryInterface $eventRepository;
    private EntityRepositoryInterface $cartEventRequestRepository;
    private CartRequestSerializer $cartRequestSerializer;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $eventRepository,
        EntityRepositoryInterface $cartEventRequestRepository,
        CartRequestSerializer $cartRequestSerializer,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->cartEventRequestRepository = $cartEventRequestRepository;
        $this->cartRequestSerializer = $cartRequestSerializer;
        $this->logger = $logger;
    }

    public function trackPlacedOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackOrderEventsForBackgroundProcessing($context, $trackingBag, self::ORDER_EVENT_PLACED);
    }

    public function trackOrderedProducts(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackOrderEventsForBackgroundProcessing(
            $context,
            $trackingBag,
            self::ORDER_EVENT_ORDERED_PRODUCT
        );
    }

    public function trackFulfilledOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackOrderEventsForBackgroundProcessing($context, $trackingBag, self::ORDER_EVENT_FULFILLED);
    }

    public function trackCanceledOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackOrderEventsForBackgroundProcessing($context, $trackingBag, self::ORDER_EVENT_CANCELED);
    }

    public function trackRefundOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackOrderEventsForBackgroundProcessing($context, $trackingBag, self::ORDER_EVENT_REFUNDED);
    }

    public function trackShippedOrder(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackOrderEventsForBackgroundProcessing($context, $trackingBag, self::ORDER_EVENT_SHIPPED);
    }

    public function trackCustomerWritten(Context $context, ProfileEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackCustomerEventsForBackgroundProcessing($context, $trackingBag, self::CUSTOMER_WRITTEN_EVENT);
    }

    public function trackAddedToCart(Context $context, CartEventRequestBag $requestBag): void
    {
        $scheduledEventRequests = [];

        foreach ($requestBag->all() as $channelId => $requests) {
            foreach ($requests as $request) {
                $scheduledEventRequests[] = [
                    'id' => Uuid::randomHex(),
                    'salesChannelId' => $channelId,
                    'serializedRequest' => $this->cartRequestSerializer->encode($request),
                ];
            }
        }

        $this->cartEventRequestRepository->create($scheduledEventRequests, $context);
    }

    private function trackCustomerEventsForBackgroundProcessing(
        Context $context,
        ProfileEventsBag $trackingBag,
        string $eventType
    ): OrderTrackingResult {
        $now = new \DateTime();
        $scheduledEvents = [];
        $result = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $customers) {
            /** @var CustomerEntity $customer */
            foreach ($customers as $customer) {
                $scheduledEvents[] = [
                    'id' => Uuid::randomHex(),
                    'type' => $eventType,
                    'entityId' => $customer->getId(),
                    'metadata' => null,
                    'salesChannelId' => $channelId,
                    'happenedAt' => $now
                ];
            }
        }

        try {
            $this->eventRepository->create($scheduledEvents, $context);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    private function trackOrderEventsForBackgroundProcessing(
        Context $context,
        OrderTrackingEventsBag $trackingBag,
        string $eventType
    ): OrderTrackingResult {
        $scheduledEvents = [];
        $result = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            /** @var Event\Order\OrderEventInterface $event */
            foreach ($events as $event) {
                $scheduledEvents[] = [
                    'id' => Uuid::randomHex(),
                    'type' => $eventType,
                    'entityId' => $event->getOrder()->getId(),
                    'metadata' => null,
                    'salesChannelId' => $channelId,
                    'happenedAt' => $event->getEventDateTime()
                ];
            }
        }

        try {
            $this->eventRepository->create($scheduledEvents, $context);
        } catch (\Throwable $e) {
            // TODO: add possibility to add simple (not order related) errors to OrderTrackingResult
            $result->addFailedOrder(
                '0',
                new TranslationException('Something is wrong with the creation of the track order event')
            );
        }

        return $result;
    }

    public function trackPaiedOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        return $this->trackOrderEventsForBackgroundProcessing($context, $trackingBag, self::ORDER_EVENT_PAID);
    }
}
