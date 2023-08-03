<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Storefront;

use Klaviyo\Integration\Storefront\Checkout\Cart\RestorerService\RestorerServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CartController extends StorefrontController
{
    private RestorerServiceInterface $restorerService;

    public function __construct(RestorerServiceInterface $restorerService)
    {
        $this->restorerService = $restorerService;
    }


    #[Route(path: '/od-restore-cart/{mappingId}', name: 'frontend.cart.od-restore-cart', options: ['seo' => false], defaults: ['_routeScope' => ['storefront']], methods: ['GET'])]
    public function index(string $mappingId, Request $request, SalesChannelContext $context): Response
    {
        $this->restorerService->restore($mappingId, $context);

        if (isset($context->customerId)) {
            $request->getSession()->set('customerId', $context->customerId);
        }

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }
}
