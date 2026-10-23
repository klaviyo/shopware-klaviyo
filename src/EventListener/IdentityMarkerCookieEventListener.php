<?php

declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\Storefront\Service\Cookie\CookieConsentService;
use Klaviyo\Integration\Storefront\Service\IdentityMarkerCookie;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Keeps the marker cookie in sync with the context on every storefront response
 * (same approach as Shopware's sw-states cookie). Login/logout override the context
 * for that request. Subscriber markers are only verified here, never minted.
 */
class IdentityMarkerCookieEventListener implements EventSubscriberInterface
{
    private const REQUEST_ATTRIBUTE_OVERRIDE = 'klaviyo_identity_marker_override';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly IdentityMarkerCookie $markerCookie,
        private readonly GetValidChannelConfig $validChannelConfig,
        private readonly CookieConsentService $cookieConsentService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerLoginEvent::class => 'onCustomerLogin',
            CustomerLogoutEvent::class => 'onCustomerLogout',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onCustomerLogin(CustomerLoginEvent $event): void
    {
        $this->requestStack->getMainRequest()?->attributes->set(
            self::REQUEST_ATTRIBUTE_OVERRIDE,
            $this->markerCookie->valueForCustomer($event->getCustomer()->getId())
        );
    }

    public function onCustomerLogout(CustomerLogoutEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        $value = null;

        // guest logout after the order: keep the identity on the finish page, the next page clears it
        if ($request?->attributes->get('_route') === 'frontend.checkout.finish.page') {
            $value = $this->markerCookie->valueForCustomer($event->getCustomer()->getId());
        }

        $request?->attributes->set(self::REQUEST_ATTRIBUTE_OVERRIDE, $value);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isStorefrontRequest($request)) {
            return;
        }

        $response = $event->getResponse();
        $expected = $this->resolveExpectedValue($request);
        $current = $this->markerCookie->read($request);

        if ($expected !== $current) {
            if (null === $expected) {
                // don't clobber a marker minted earlier in this response (newsletter confirmation)
                if (!$this->markerCookie->isSetOn($response)) {
                    $this->markerCookie->clear($request, $response);
                }
            } else {
                $this->markerCookie->set($request, $response, $expected);
            }
        }

        // a response that writes the marker is personal, keep it out of the HTTP cache
        if (null !== $this->markerCookie->findOn($response)) {
            $request->attributes->set(PlatformRequest::ATTRIBUTE_NO_STORE, true);
        }
    }

    private function resolveExpectedValue(Request $request): ?string
    {
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        if (!$context instanceof SalesChannelContext) {
            return null;
        }

        // marketing cookie: only with a configured channel and Klaviyo consent
        $config = $this->validChannelConfig->execute($context->getSalesChannelId());
        if (null === $config || !$this->cookieConsentService->hasConsent($config->getCookieConsent())) {
            return null;
        }

        if ($request->attributes->has(self::REQUEST_ATTRIBUTE_OVERRIDE)) {
            $override = $request->attributes->get(self::REQUEST_ATTRIBUTE_OVERRIDE);

            return \is_string($override) ? $override : null;
        }

        if (null !== $context->getCustomer()) {
            return $this->markerCookie->valueForCustomer($context->getCustomer()->getId());
        }

        $subscriberId = $request->cookies->get(AddedToCartEventListener::SUBSCRIBER_COOKIE);
        if (\is_string($subscriberId)
            && $subscriberId !== ''
            && $this->markerCookie->isValidForSubscriber($request, $subscriberId)
        ) {
            return $this->markerCookie->read($request);
        }

        return null;
    }

    private function isStorefrontRequest(Request $request): bool
    {
        return \in_array(
            StorefrontRouteScope::ID,
            (array) $request->attributes->get(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, []),
            true
        );
    }
}
