<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Klaviyo\Gateway\Translator\CartEventRequestTranslator;
use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Event\GuestCustomerRegisterEvent;

class GuestCustomerRegisterEventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly CartEventRequestTranslator $cartEventRequestTranslator,
        private readonly EventsTrackerInterface $eventsTracker,
        private readonly LoggerInterface $logger,
        private readonly GetValidChannelConfig $getValidChannelConfig,
        private readonly CartService $cartService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            GuestCustomerRegisterEvent::class => 'onGuestRegister',
        ];
    }

    /**
     * @param GuestCustomerRegisterEvent $event
     * @return void
     */
    public function onGuestRegister (GuestCustomerRegisterEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        if ($salesChannelContext->getToken()) {
            try {

                $config = $this->getValidChannelConfig->execute($event->getSalesChannelContext()->getSalesChannelId());

                if (null === $config || !$config->isTrackAddedToCart()) {
                    return;
                }

                $now = new \DateTime('now', new \DateTimeZone('UTC'));
                $requestBag = new CartEventRequestBag();

                $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

                if ($cart && count($cart->getLineItems()) > 0) {
                    /** @var LineItem $lineItem */
                    foreach ($cart->getLineItems() as $lineItem) {
                        if (LineItem::PRODUCT_LINE_ITEM_TYPE !== $lineItem->getType()) {
                            continue;
                        }

                        $lineItemEntity = $cart->get($lineItem->getId());

                        if (null === $lineItemEntity) {
                            $this->logger->error('Item added to the cart is null, lineItem ID=' . $lineItem->getId());
                            continue;
                        }

                        $requestBag->add(
                            $this->cartEventRequestTranslator->translateToAddedToCartEventRequest(
                                $salesChannelContext,
                                $cart,
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
                }
            } catch (\Exception $e) {
                $this->logger->error('Error tracking guest cart: ' . $e->getMessage(), [
                    'exception' => $e,
                    'token' => $salesChannelContext->getToken()
                ]);
            }
        }
    }
}
