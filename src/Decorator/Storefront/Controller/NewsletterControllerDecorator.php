<?php declare(strict_types=1);

namespace Klaviyo\Integration\Decorator\Storefront\Controller;

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

   public function __construct(
       NewsletterSubscribePageLoader $newsletterConfirmRegisterPageLoader,
       EntityRepositoryInterface $customerRepository,
       AbstractNewsletterSubscribeRoute $newsletterSubscribeRoute,
       AbstractNewsletterConfirmRoute $newsletterConfirmRoute,
       AbstractNewsletterUnsubscribeRoute $newsletterUnsubscribeRoute,
       NewsletterAccountPageletLoader $newsletterAccountPageletLoader,
       SystemConfigService $systemConfigService,
       string $swVersion
   ) {
       if (version_compare($swVersion, '6.4.18.1', '<')) {
           // Before 6.4.18.1
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
        if (!$request->cookies->get('od-klaviyo-track-allow')) {
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
}
