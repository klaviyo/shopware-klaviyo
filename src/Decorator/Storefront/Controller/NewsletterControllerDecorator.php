<?php declare(strict_types=1);

namespace Klaviyo\Integration\Decorator\Storefront\Controller;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterConfirmRoute;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterSubscribeRoute;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterUnsubscribeRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\QueryDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\NewsletterController;
use Shopware\Storefront\Page\Newsletter\Subscribe\NewsletterSubscribePageLoader;
use Shopware\Storefront\Pagelet\Newsletter\Account\NewsletterAccountPageletLoader;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function version_compare;


class NewsletterControllerDecorator
{
    private GetValidChannelConfig $validChannelConfig;

    private \Shopware\Storefront\Controller\NewsletterController $decoratedService;

    /**
     * @internal
     */
    public function __construct(
        \Shopware\Storefront\Controller\NewsletterController $newsletterController,
        GetValidChannelConfig $validChannelConfig
    ) {
        $this->decoratedService = $newsletterController;
        $this->validChannelConfig = $validChannelConfig;
    }

    public function getDecorated(): \Shopware\Storefront\Controller\NewsletterController
    {
        return $this->decoratedService;
    }

    public function subscribeMail(SalesChannelContext $context, Request $request, QueryDataBag $queryDataBag): Response
    {
        $response = $this->getDecorated()->subscribeMail($context, $request, $queryDataBag);

        if (!$this->isCookieAllowed($context, $request)) {
            return $response;
        }

        if ($context->getContext()->hasExtension('klaviyo_subscriber_id')) {
            $subscriberExtension = $context->getContext()->getExtension('klaviyo_subscriber_id');
            $subscriberId = $subscriberExtension !== null ? $subscriberExtension[0] : null;

            if ($subscriberId) {
                $cookie = Cookie::create('klaviyo_subscriber', $subscriberId);
                $cookie->setSecureDefault($request->isSecure());
                $response->headers->setCookie($cookie);
            }
        }

        return $response;
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

    private function isCookieBotAllowed(Request $request) : bool {
        $data = $request->cookies->get('CookieConsent');
        if (!$data) {
            return false;
        }
        // cookiebot official
        $valid_php_json = preg_replace('/\s*:\s*([a-zA-Z0-9_]+?)([}\[,])/', ':"$1"$2', preg_replace('/([{\[,])\s*([a-zA-Z0-9_]+?):/', '$1"$2":', str_replace("'", '"', stripslashes($data))));
        $CookieConsent = json_decode($valid_php_json, true);
        return empty($CookieConsent['marketing']) ? false : $CookieConsent['marketing'] === 'true';
    }
}
