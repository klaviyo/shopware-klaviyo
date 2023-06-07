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
#[Route(defaults: ['_routeScope' => ['api']])]
class CartController extends StorefrontController
{
    private RestorerServiceInterface $restorerService;

    public function __construct(RestorerServiceInterface $restorerService)
    {
        $this->restorerService = $restorerService;
    }

    /**
     * @Route("", name="", options={"seo"=false}, methods={"GET"})
     */
    #[Route(path: '/od-restore-cart/{mappingId}', name: 'frontend.cart.od-restore-cart', options: ['seo' => false], methods: ['POST'])]
    public function index(string $mappingId, SalesChannelContext $context): Response
    {
        $this->restorerService->restore($mappingId, $context);

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }
}
