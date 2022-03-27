<?php

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Klaviyo\Gateway\Translator\CartEventRequestTranslator;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\{AfterLineItemAddedEvent, AfterLineItemQuantityChangedEvent};
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddedToCartEventListener implements EventSubscriberInterface
{
    private CartEventRequestTranslator $cartEventRequestTranslator;
    private EventsTrackerInterface $eventsTracker;
    private LoggerInterface $logger;

    public function __construct(
        CartEventRequestTranslator $cartEventRequestTranslator,
        EventsTrackerInterface $eventsTracker,
        LoggerInterface $logger
    ) {
        $this->cartEventRequestTranslator = $cartEventRequestTranslator;
        $this->eventsTracker = $eventsTracker;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterLineItemAddedEvent::class => 'onAfterLineItemAdded',
            AfterLineItemQuantityChangedEvent::class => 'onLineItemQuantityChanged',
        ];
    }

    public function onAfterLineItemAdded(AfterLineItemAddedEvent $event)
    {
        try {
            $salesChannelContext = $event->getSalesChannelContext();
            if (!$salesChannelContext->getCustomer() && !isset($_COOKIE["klaviyo_subscriber"])) {
                return;
            }

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $requestBag = new CartEventRequestBag();

            /** @var LineItem $lineItem */
            foreach ($event->getLineItems() as $lineItem) {
                $requestBag->add(
                    $this->cartEventRequestTranslator->translateToAddedToCartEventRequest(
                        $salesChannelContext,
                        $event->getCart(),
                        $event->getCart()->get($lineItem->getId()),
                        $now
                    ),
                    $salesChannelContext->getSalesChannelId()
                );
            }

            $this->eventsTracker->trackAddedToCart($salesChannelContext->getContext(), $requestBag);
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
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $cart = $event->getCart();
            $salesChannelContext = $event->getSalesChannelContext();
            if (!$salesChannelContext->getCustomer() && !isset($_COOKIE["klaviyo_subscriber"])) {
                return;
            }

            $requestBag = new CartEventRequestBag();

            /** @var LineItem $lineItem */
            foreach ($event->getItems() as $itemData) {
                $requestBag->add(
                    $this->cartEventRequestTranslator->translateToAddedToCartEventRequest(
                        $salesChannelContext,
                        $event->getCart(),
                        $cart->getLineItems()->get($itemData['id']),
                        $now
                    ),
                    $salesChannelContext->getSalesChannelId()
                );
            }

            $this->eventsTracker->trackAddedToCart(Context::createDefaultContext(), $requestBag);
        } catch (\Throwable $throwable) {
            $this->logger
                ->error(
                    'Could not track Add to Cart event after the item qty updated',
                    ContextHelper::createContextFromException($throwable)
                );
        }
    }
}
