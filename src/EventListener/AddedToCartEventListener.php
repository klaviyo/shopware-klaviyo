<?php declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Klaviyo\Gateway\Translator\CartEventRequestTranslator;
use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\{AfterLineItemAddedEvent, AfterLineItemQuantityChangedEvent};
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AddedToCartEventListener implements EventSubscriberInterface
{
    private CartEventRequestTranslator $cartEventRequestTranslator;
    private EventsTrackerInterface $eventsTracker;
    private LoggerInterface $logger;
    private RequestStack $requestStack;
    private GetValidChannelConfig $getValidChannelConfig;

    public function __construct(
        CartEventRequestTranslator $cartEventRequestTranslator,
        EventsTrackerInterface $eventsTracker,
        LoggerInterface $logger,
        RequestStack $requestStack,
        GetValidChannelConfig $getValidChannelConfig
    ) {
        $this->cartEventRequestTranslator = $cartEventRequestTranslator;
        $this->eventsTracker = $eventsTracker;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->getValidChannelConfig = $getValidChannelConfig;
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
            $config = $this->getValidChannelConfig->execute($event->getSalesChannelContext()->getSalesChannelId());
            if ($config === null || !$config->isTrackAddedToCart()) {
                return;
            }

            $salesChannelContext = $event->getSalesChannelContext();
            $request = $this->requestStack->getCurrentRequest();
            if (!$salesChannelContext->getCustomer() && !$request->cookies->get('klaviyo_subscriber')) {
                return;
            }

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $requestBag = new CartEventRequestBag();

            /** @var LineItem $lineItem */
            foreach ($event->getLineItems() as $lineItem) {
                $lineItemEntity = $event->getCart()->get($lineItem->getId());

                if (null === $lineItemEntity) {
                    $this->logger->error('Item added to the cart is null, lineItem ID=' . $lineItem->getId());
                    continue;
                }

                $requestBag->add(
                    $this->cartEventRequestTranslator->translateToAddedToCartEventRequest(
                        $salesChannelContext,
                        $event->getCart(),
                        $lineItemEntity,
                        $now
                    ),
                    $salesChannelContext->getSalesChannelId()
                );
            }

            if (empty($requestBag->all())) {
                return;
            }

            $this->eventsTracker->trackAddedToCart($salesChannelContext->getContext(), $requestBag);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Could not track Add to Cart event after new item added to the cart',
                ContextHelper::createContextFromException($throwable)
            );
        }
    }

    public function onLineItemQuantityChanged(AfterLineItemQuantityChangedEvent $event)
    {
        try {
            $config = $this->getValidChannelConfig->execute($event->getSalesChannelContext()->getSalesChannelId());
            if ($config === null || !$config->isTrackAddedToCart()) {
                return;
            }

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $cart = $event->getCart();
            $salesChannelContext = $event->getSalesChannelContext();
            $request = $this->requestStack->getCurrentRequest();
            if (!$salesChannelContext->getCustomer() && !$request->cookies->get('klaviyo_subscriber')) {
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

            $this->eventsTracker->trackAddedToCart($event->getContext(), $requestBag);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Could not track Add to Cart event after the item qty updated',
                ContextHelper::createContextFromException($throwable)
            );
        }
    }
}
