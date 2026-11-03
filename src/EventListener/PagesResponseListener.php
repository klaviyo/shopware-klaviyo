<?php
declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Storefront\Service\IdentityMarkerCookie;
use Shopware\Storefront\Framework\Routing\Annotation\NoStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class PagesResponseListener implements EventSubscriberInterface
{
    private IdentityMarkerCookie $identityMarkerCookie;

    public function __construct(IdentityMarkerCookie $identityMarkerCookie)
    {
        $this->identityMarkerCookie = $identityMarkerCookie;
    }

    public static function getSubscribedEvents(): array
    {
        return [ResponseEvent::class => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $session = $event->getRequest()->getSession();

        if ($subscriberId = $session->get('klaviyo_subscriber_id')) {
            $response = $event->getResponse();
            $request = $event->getRequest();

            $cookie = Cookie::create('klaviyo_subscriber', $subscriberId);
            $cookie->setSecureDefault($request->isSecure());
            $response->headers->setCookie($cookie);
            $session->set('klaviyo_subscriber_id', null);

            // the marker tells the JS to load the identity; the subscriber cookie alone is never trusted
            $this->identityMarkerCookie->set(
                $request,
                $response,
                $this->identityMarkerCookie->valueForSubscriber((string) $subscriberId)
            );

            // personal response, keep it out of the HTTP cache
            $request->attributes->set('_' . NoStore::ALIAS, true);
        }
    }
}
