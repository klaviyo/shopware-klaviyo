<?php declare(strict_types=1);

namespace Klaviyo\Integration\Decorator\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\QueryDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\NewsletterController;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteScope(scopes={"storefront"})
 */
class NewsletterControllerDecorator extends StorefrontController
{
    private NewsletterController $inner;

    public function __construct(NewsletterController $inner)
    {
        $this->inner = $inner;
    }

    public function subscribeMail(SalesChannelContext $context, Request $request, QueryDataBag $queryDataBag): Response
    {
        $response = $this->inner->subscribeMail($context, $request, $queryDataBag);

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
}
