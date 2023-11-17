<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Storefront;

use Klaviyo\Integration\Storefront\Checkout\Cart\RestorerService\RestorerServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        $this->restorerService->restore($mappingId, $context);

        if (isset($context->customerId)) {
            $request->getSession()->set('customerId', $context->customerId);
            $restorerService =  $this->restorerService;
            /** @var \Klaviyo\Integration\Storefront\Checkout\Cart\RestorerService\RestorerService $restorerService */
            $data = $restorerService->registerCustomerByRestoreCartLink($context);


            if ($data->count() > 0) {
                try {
                    $this->registerRoute->register(
                        $data->toRequestDataBag(),
                        $context,
                        false
                    );
                } catch (\Exception $exception) {
                    $this->logger->error($exception->getMessage());
                }
            }
        }

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }
}
