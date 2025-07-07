<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Storefront;

use Klaviyo\Integration\Storefront\Checkout\Cart\RestorerService\RestorerServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Psr\Log\LoggerInterface;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CartController extends StorefrontController
{
    private RestorerServiceInterface $restorerService;
    private AbstractRegisterRoute $registerRoute;
    private LoggerInterface $logger;

    public function __construct(
        RestorerServiceInterface $restorerService,
        AbstractRegisterRoute $registerRoute,
        LoggerInterface $logger
    ) {
        $this->restorerService = $restorerService;
        $this->registerRoute = $registerRoute;
        $this->logger = $logger;
    }


    #[Route(path: '/od-restore-cart/{mappingId}', name: 'frontend.cart.od-restore-cart', options: ['seo' => false], defaults: ['_routeScope' => ['storefront']], methods: ['GET'])]
    public function index(string $mappingId, Request $request, SalesChannelContext $context): Response
    {
        $status = $this->restorerService->restore($mappingId, $context);

        if ($status && ($context->getCustomer() || isset($context->customerId))) {
            $request->getSession()->set('customerId', $context->customerId ?? $context->getCustomer()->getId());
            if (!$context->getCustomer()) {
                $data = $this->restorerService->registerCustomerByRestoreCartLink($context);

                if ($data->count() > 0) {
                    try {
                        $this->registerRoute->register(
                            $data->toRequestDataBag(),
                            $context,
                            false
                        );
                    } catch (\Exception $exception) {
                        $this->logger->error($exception->getMessage());
                        $this->addFlash(self::DANGER, $this->trans('klaviyo.cart-restore.missing-cart-data-error'));
                        return $this->redirectToRoute('frontend.home.page');
                    }
                } else {
                    $this->addFlash(self::WARNING, $this->trans('klaviyo.cart-restore.missing-cart-data'));
                    return $this->redirectToRoute('frontend.home.page');
                }
            }
        } elseif (false === $status) {
            $this->addFlash(self::WARNING, $this->trans('klaviyo.cart-restore.missing-cart-data-guest'));
        } else {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }
}
