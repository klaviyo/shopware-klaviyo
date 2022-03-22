<?php declare(strict_types=1);

namespace Klaviyo\Integration\Controller\Storefront;

use Klaviyo\Integration\Klaviyo\Gateway\Translator\BackInStockEventRequestTranslator;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class BackInStockController extends StorefrontController
{
    /**
     * @Route("/klaviyo/back-in-stock/subscribe", name="frontend.klaviyo.back-in-stock.subscribe", methods={"POST"})
     */
    public function onSubscribedToBackInStock(
        Request $request,
        SalesChannelContext $salesChannelContext
    ): Response {
        $productId = $request->get('productId');
        $email = $request->get('email');
        $email = is_null($email) && !empty($customer) ? $customer->getEmail() : $email;

        if (empty($productId) || empty($email)) {
            return $this->json([
                "success" => false,
                "data" => [
                    'key' => null,
                    'success' => false,
                    'result' => [
                        'error' => 'No email or productId provided.',
                    ],
                ],
            ]);
        }

        return $this->json([
            "success" => true,
            "data" => [
                'key' => null,
                'success' => true,
                'result' => [
                    'error' => null,
                ],
            ],
        ]);
    }
}