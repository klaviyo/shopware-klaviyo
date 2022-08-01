<?php declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\System\Tracking\{Event\Order, EventsTrackerInterface};
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutOrderPlacedEventListener implements EventSubscriberInterface
{
    private EventsTrackerInterface $eventsTracker;
    private GetValidChannelConfig $getValidChannelConfig;

    public function __construct(
        EventsTrackerInterface $eventsTracker,
        GetValidChannelConfig $getValidChannelConfig
    ) {
        $this->eventsTracker = $eventsTracker;
        $this->getValidChannelConfig = $getValidChannelConfig;
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event)
    {
        $config = $this->getValidChannelConfig->execute($event->getSalesChannelId());
        if ($config === null || !$config->isTrackPlacedOrder()) {
            return;
        }

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
