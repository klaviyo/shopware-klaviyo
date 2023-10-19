<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Decorator\Storefront\Controller;

use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Validation\DataBag\QueryDataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\NewsletterController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewsletterControllerDecorator
{
    /**
     * @internal
     */
    public function __construct(
        private readonly NewsletterController $newsletterController,
        private readonly GetValidChannelConfig $validChannelConfig
    ) {
    }

    public function getDecorated(): NewsletterController
    {
        return $this->newsletterController;
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/newsletter-subscribe', name: 'frontend.newsletter.subscribe', methods: ['GET'])]
    public function subscribeMail(SalesChannelContext $context, Request $request, QueryDataBag $queryDataBag): Response
    {
        $response = $this->getDecorated()->subscribeMail($context, $request, $queryDataBag);

        if (!$this->isCookieAllowed($context, $request)) {
            return $response;
        }

        if ($context->getContext()->hasExtension('klaviyo_subscriber_id')) {
            $subscriberExtension = $context->getContext()->getExtension('klaviyo_subscriber_id');
            $subscriberId = null !== $subscriberExtension ? $subscriberExtension[0] : null;

            if ($subscriberId) {
                $cookie = Cookie::create('klaviyo_subscriber', $subscriberId);
                $cookie->setSecureDefault($request->isSecure());
                $response->headers->setCookie($cookie);
            }
        }

        return $response;
    }

    #[Route(path: '/widgets/account/newsletter', name: 'frontend.account.newsletter', defaults: ['XmlHttpRequest' => true, '_loginRequired' => true], methods: ['POST'])]
    public function subscribeCustomer(
        Request $request,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        CustomerEntity $customer
    ): Response {
        return $this->getDecorated()->subscribeCustomer($request, $dataBag, $context, $customer);
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
        $valid_php_json = preg_replace(
            '/\s*:\s*([a-zA-Z0-9_]+?)([}\[,])/',
            ':"$1"$2',
            preg_replace(
                '/([{\[,])\s*([a-zA-Z0-9_]+?):/',
                '$1"$2":',
                str_replace("'", '"', stripslashes($data))
            )
        );

        $CookieConsent = json_decode($valid_php_json, true);

        return !empty($CookieConsent['marketing']) && 'true' === $CookieConsent['marketing'];
    }
}
