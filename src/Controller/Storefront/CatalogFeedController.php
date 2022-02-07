<?php

namespace Klaviyo\Integration\Controller\Storefront;

use Klaviyo\Integration\Tracking\TrackingCatalogFeedProvider;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(
 *     "klaviyo/integration/catalog-feed"
 * )
 */
class CatalogFeedController
{
    private TrackingCatalogFeedProvider $trackingCatalogFeedProvider;

    public function __construct(TrackingCatalogFeedProvider $trackingCatalogFeedProvider)
    {
        $this->trackingCatalogFeedProvider = $trackingCatalogFeedProvider;
    }

    /**
     * @Route(
     *     "/get",
     *     name="klaviyo.integration.get.catalog.feed",
     *     methods={"GET"},
     *     requirements={"version"="\d+"}
     * )
     * @return JsonResponse
     */
    public function getCatalogFeedAction(SalesChannelContext $context)
    {
        $catalogFeed = $this->trackingCatalogFeedProvider->getCatalogFeed($context);

        return new JsonResponse($catalogFeed);
    }
}
