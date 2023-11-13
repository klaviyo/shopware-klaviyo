<?php
declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class PagesResponseListener implements EventSubscriberInterface
{
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
        }
    }
}
