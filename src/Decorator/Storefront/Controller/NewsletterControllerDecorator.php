<?php declare(strict_types=1);

namespace Klaviyo\Integration\Decorator\Storefront\Controller;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterConfirmRoute;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterSubscribeRoute;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterUnsubscribeRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
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
use function version_compare;

/**
 * @RouteScope(scopes={"storefront"})
 */
class NewsletterControllerDecorator extends NewsletterController
{

    private GetValidChannelConfig $validChannelConfig;

    public function __construct(
        NewsletterSubscribePageLoader      $newsletterConfirmRegisterPageLoader,
        EntityRepositoryInterface          $customerRepository,
        AbstractNewsletterSubscribeRoute   $newsletterSubscribeRoute,
        AbstractNewsletterConfirmRoute     $newsletterConfirmRoute,
        AbstractNewsletterUnsubscribeRoute $newsletterUnsubscribeRoute,
        NewsletterAccountPageletLoader     $newsletterAccountPageletLoader,
        GetValidChannelConfig              $validChannelConfig,
        SystemConfigService                $systemConfigService,
        string                             $swVersion
    )
    {
        $this->validChannelConfig = $validChannelConfig;
        if (version_compare($swVersion, '6.4.18.1', '<')) {
            // Before 6.4.18.1
            // @phpstan-ignore-next-line
            parent::__construct(
                $newsletterConfirmRegisterPageLoader,
                $customerRepository,
                $newsletterSubscribeRoute,
                $newsletterConfirmRoute,
                $newsletterUnsubscribeRoute,
                $newsletterAccountPageletLoader
            );
        } else {
            // From 6.4.18.1 (included)
            parent::__construct(
                $newsletterConfirmRegisterPageLoader,
                $customerRepository,
                $newsletterSubscribeRoute,
                $newsletterConfirmRoute,
                $newsletterUnsubscribeRoute,
                $newsletterAccountPageletLoader,
                $systemConfigService
            );
        }
    }

    public function subscribeMail(SalesChannelContext $context, Request $request, QueryDataBag $queryDataBag): Response
    {
        $response = parent::subscribeMail($context, $request, $queryDataBag);

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
