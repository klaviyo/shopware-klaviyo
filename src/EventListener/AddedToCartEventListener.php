<?php

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Tracking\EventsTracker;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\AfterLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\AfterLineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddedToCartEventListener implements EventSubscriberInterface
{
    private EventsTracker $eventsTracker;
    private LoggerInterface $logger;

    public function __construct(EventsTracker $eventsTracker, LoggerInterface $logger)
    {
        $this->eventsTracker = $eventsTracker;
        $this->logger = $logger;
    }

    public function onAfterLineItemAdded(AfterLineItemAddedEvent $event)
    {
        try {
            $salesChannelContext = $event->getSalesChannelContext();
            if (!$salesChannelContext->getCustomer()) {
                return;
            }

            $cart = $event->getCart();

            /** @var LineItem $lineItem */
            foreach ($event->getLineItems() as $lineItem) {
                $this->eventsTracker->trackAddedToCart($salesChannelContext, $cart, $lineItem);
            }
        } catch (\Throwable $throwable) {
            $this->logger
                ->error(
                    'Could not track Add to Cart event after new item added to the cart',
                    ContextHelper::createContextFromException($throwable)
                );
        }
    }

    public function onLineItemQuantityChanged(AfterLineItemQuantityChangedEvent $event)
    {
        try {
            $cart = $event->getCart();
            $salesChannelContext = $event->getSalesChannelContext();
            if (!$salesChannelContext->getCustomer()) {
                return;
            }

            $lineItems = [];
            /** @var LineItem $lineItem */
            foreach ($event->getItems() as $itemData) {
                $lineItems[] = $cart->getLineItems()->get($itemData['id']);
            }

            foreach ($lineItems as $lineItem) {
                $this->eventsTracker->trackAddedToCart($salesChannelContext, $cart, $lineItem);
            }
        } catch (\Throwable $throwable) {
            $this->logger
                ->error(
                    'Could not track Add to Cart event after the item qty updated',
                    ContextHelper::createContextFromException($throwable)
                );
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            AfterLineItemAddedEvent::class => 'onAfterLineItemAdded',
            AfterLineItemQuantityChangedEvent::class => 'onLineItemQuantityChanged',
        ];
    }
}