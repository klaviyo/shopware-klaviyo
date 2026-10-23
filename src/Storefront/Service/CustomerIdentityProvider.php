<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Storefront\Service;

use Klaviyo\Integration\Entity\Helper\NewsletterSubscriberHelper;
use Klaviyo\Integration\EventListener\AddedToCartEventListener;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\CustomerPropertiesTranslator;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\NewsletterSubscriberPropertiesTranslator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the visitor's Klaviyo profile properties. Only exposed
 * via the no-store identity endpoint.
 */
class CustomerIdentityProvider
{
    public function __construct(
        private readonly CustomerPropertiesTranslator $customerPropertiesTranslator,
        private readonly NewsletterSubscriberHelper $newsletterSubscriberHelper,
        private readonly NewsletterSubscriberPropertiesTranslator $newsletterSubscriberPropertiesTranslator,
        private readonly IdentityMarkerCookie $identityMarkerCookie,
    ) {
    }

    public function getIdentity(SalesChannelContext $channelContext, Request $request): ?CustomerProperties
    {
        if ($customer = $channelContext->getCustomer()) {
            return $this->customerPropertiesTranslator->translateCustomer(
                $channelContext->getContext(),
                $customer
            );
        }

        $subscriberId = $request->cookies->get(AddedToCartEventListener::SUBSCRIBER_COOKIE);
        if (!\is_string($subscriberId) || $subscriberId === '') {
            return null;
        }

        // the subscriber cookie can be forged, the marker can't
        if (!$this->identityMarkerCookie->isValidForSubscriber($request, $subscriberId)) {
            return null;
        }

        $subscriber = $this->newsletterSubscriberHelper->getSubscriber(
            $subscriberId,
            $channelContext->getContext()
        );

        // the marker is host-wide, the subscription is per sales channel
        if (null === $subscriber || $subscriber->getSalesChannelId() !== $channelContext->getSalesChannelId()) {
            return null;
        }

        return $this->newsletterSubscriberPropertiesTranslator->translateSubscriber($subscriber);
    }
}
