<?php

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Tracking\EventsTracker;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutOrderPlacedEventListener implements EventSubscriberInterface
{
    private EventsTracker $eventsTracker;
    private EntityRepositoryInterface $salesChannelRepository;

    public function __construct(EventsTracker $eventsTracker, EntityRepositoryInterface $salesChannelRepository)
    {
        $this->eventsTracker = $eventsTracker;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event)
    {
        $salesChannel = $this->salesChannelRepository
            ->search(new Criteria([$event->getSalesChannelId()]), $event->getContext())->first();

        $this->eventsTracker->trackPlacedOrder($event->getContext(), $salesChannel, $event->getOrder());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }
}
