<?php

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\System\Tracking\Event\Order;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutOrderPlacedEventListener implements EventSubscriberInterface
{
    private EventsTrackerInterface $eventsTracker;

    public function __construct(EventsTrackerInterface $eventsTracker)
    {
        $this->eventsTracker = $eventsTracker;
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event)
    {
        $eventsBag = new Order\OrderTrackingEventsBag();
        $orderPlacedEvent = new Order\OrderEvent($event->getOrder());
        $eventsBag->add($orderPlacedEvent);

        $this->eventsTracker->trackPlacedOrders($event->getContext(), $eventsBag);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }
}
