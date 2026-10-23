<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Storefront;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Model\Channel\GetValidChannelConfig;
use Klaviyo\Integration\Storefront\Service\CustomerIdentityProvider;
use Klaviyo\Integration\Utils\Logger\ContextHelper;
use Psr\Log\LoggerInterface;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Under /account/ so the default Shopware Varnish config never caches it; response is no-store.
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class IdentityController extends StorefrontController
{
    public function __construct(
        private readonly GetValidChannelConfig $getValidChannelConfig,
        private readonly CustomerIdentityProvider $customerIdentityProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        path: '/account/klaviyo/identity',
        name: 'frontend.klaviyo.identity',
        options: ['seo' => false],
        defaults: [
            'XmlHttpRequest' => true,
            '_routeScope' => ['storefront'],
            PlatformRequest::ATTRIBUTE_NO_STORE => true,
        ],
        methods: ['GET']
    )]
    public function identity(Request $request, SalesChannelContext $context): JsonResponse
    {
        $identity = null;

        try {
            $configuration = $this->getValidChannelConfig->execute($context->getSalesChannelId());

            if (null !== $configuration) {
                $identity = $this->customerIdentityProvider->getIdentity($context, $request);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error(
                \sprintf('Could not resolve Klaviyo customer identity, reason: %s', $throwable->getMessage()),
                ContextHelper::createContextFromException($throwable)
            );

            // non-2xx so the storefront does not cache this as an anonymous visitor; it retries on the next page view
            return $this->createResponse(null, Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->createResponse($identity, Response::HTTP_OK);
    }

    private function createResponse(?CustomerProperties $identity, int $status): JsonResponse
    {
        $response = new JsonResponse(['identity' => $identity], $status);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->setMaxAge(0);

        return $response;
    }
}
