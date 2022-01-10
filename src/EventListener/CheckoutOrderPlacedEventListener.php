<?php

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\System\Tracking\Event\OrderEvent;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Klaviyo\Integration\System\Tracking\OrderTrackingEventsBag;
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
        $eventsBag = new OrderTrackingEventsBag();
        $orderPlacedEvent = new OrderEvent($event->getOrder());
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
