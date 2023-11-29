<?php
declare(strict_types=1);

namespace Klaviyo\Integration\EventListener;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Newsletter\Subscribe\NewsletterSubscribePageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class NewsletterSubscribePageLoadedEventListener implements EventSubscriberInterface
{
    private GetValidChannelConfig $validChannelConfig;

    public function __construct(GetValidChannelConfig $validChannelConfig)
    {
        $this->validChannelConfig = $validChannelConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NewsletterSubscribePageLoadedEvent::class => 'checkCookie'
        ];
    }

    public function checkCookie(NewsletterSubscribePageLoadedEvent $event): void
    {
        $context = $event->getSalesChannelContext();
        $request = $event->getRequest();
        $session = $event->getRequest()->getSession();

        if ($this->isCookieAllowed($context, $request) &&
            $context->getContext()->hasExtension('klaviyo_subscriber_id')) {
            $subscriberExtension = $context->getContext()->getExtension('klaviyo_subscriber_id');
            $subscriberId = $subscriberExtension !== null ? $subscriberExtension[0] : null;

            if ($subscriberId) {
                $session->set('klaviyo_subscriber_id', $subscriberId);
            }
        }
    }

    private function isCookieAllowed(SalesChannelContext $context, Request $request)
    {
        $cookieType = $this->validChannelConfig->execute($context->getSalesChannelId())->getCookieConsent();
        switch ($cookieType) {
            case 'shopware':
            case 'consentmanager':
                return $request->cookies->get('od-klaviyo-track-allow');
            case 'cookiebot':
                return $this->isCookieBotAllowed($request);
            default:
                return true;
        }
    }

    private function isCookieBotAllowed(Request $request): bool
    {
        $data = $request->cookies->get('CookieConsent');
        if (!$data) {
            return false;
        }

        // Cookiebot official
        $valid_php_json = preg_replace('/\s*:\s*([a-zA-Z0-9_]+?)([}\[,])/', ':"$1"$2', preg_replace('/([{\[,])\s*([a-zA-Z0-9_]+?):/', '$1"$2":', str_replace("'", '"', stripslashes($data))));
        $CookieConsent = json_decode($valid_php_json, true);
        return !empty($CookieConsent['marketing']) && $CookieConsent['marketing'] === 'true';
    }
}
