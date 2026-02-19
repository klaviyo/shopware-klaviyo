<?php declare(strict_types=1);

namespace Klaviyo\Integration\Storefront\Subscriber;

use Klaviyo\Integration\Storefront\Checkout\Cart\RestorerService\RestorerService;
use Shopware\Core\Checkout\Cart\Event\BeforeCartMergeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RestoreCartSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCartMergeEvent::class => 'beforeCartMerge',
        ];
    }

    public function beforeCartMerge(BeforeCartMergeEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $restoredLineItems = $session->get(RestorerService::CART_RESTORE_SESSION, []);
        $mergeableLineItems = $event->getMergeableLineItems();
        $lineItemIdsToRemove = [];

        foreach ($mergeableLineItems as $lineItem) {
            // Remove duplicate line items that were already restored by the RestorerService.
            $lineItemId = $lineItem->getId();
            if (array_key_exists($lineItemId, $restoredLineItems)) {
                $lineItemIdsToRemove[] = $lineItemId;
                unset($restoredLineItems[$lineItemId]);
            }
        }

        foreach ($lineItemIdsToRemove as $lineItemId) {
            $mergeableLineItems->remove($lineItemId);
        }
        $session->set(RestorerService::CART_RESTORE_SESSION, $restoredLineItems);
    }
}
