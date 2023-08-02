<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Storefront;

use Klaviyo\Integration\Storefront\Checkout\Cart\RestorerService\RestorerServiceInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CartController extends StorefrontController
{
    private RestorerServiceInterface $restorerService;

    public function __construct(RestorerServiceInterface $restorerService)
    {
        $this->restorerService = $restorerService;
    }

    /**
     * @Route("/od-restore-cart/{mappingId}", name="frontend.cart.od-restore-cart", options={"seo"=false}, methods={"GET"})
     */
    public function index(string $mappingId, SalesChannelContext $context): Response
    {
        $this->restorerService->restore($mappingId, $context);

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }
}
