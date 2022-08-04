<?php

namespace Klaviyo\Integration\Klaviyo\FrontendApi\Translator;

use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\CheckoutLineItemInfo;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\CheckoutLineItemInfoCollection;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\StartedCheckoutEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StartedCheckoutEventTrackingRequestTranslator
{
    private UrlGeneratorInterface $urlGenerator;
    private ProductDataHelper $productDataHelper;

    public function __construct(UrlGeneratorInterface $urlGenerator, ProductDataHelper $productDataHelper)
    {
        $this->urlGenerator = $urlGenerator;
        $this->productDataHelper = $productDataHelper;
    }

    public function translate(SalesChannelContext $context, Cart $cart): StartedCheckoutEventTrackingRequest
    {
        $checkoutUrl = $this->urlGenerator
            ->generate(
                'frontend.checkout.confirm.page',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

        $lineItems = $this->translateToCheckoutLineItems($context, $cart);

        return new StartedCheckoutEventTrackingRequest(
            $cart->getToken(),
            $checkoutUrl,
            $cart->getPrice()->getTotalPrice(),
            $lineItems
        );
    }

    private function translateToCheckoutLineItems(
        SalesChannelContext $context,
        Cart $cart
    ): CheckoutLineItemInfoCollection {
        $collection = new CheckoutLineItemInfoCollection();

        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== 'product') {
                continue;
            }

            $translatedItem = $this->translateLineItem($context, $lineItem);
            $collection->add($translatedItem);
        }

        return $collection;
    }

    private function translateLineItem(SalesChannelContext $context, LineItem $lineItem): CheckoutLineItemInfo
    {
        $product = $this->productDataHelper->getProductById($context->getContext(), $lineItem->getReferencedId());
        if (!$product) {
            throw new TranslationException(
                sprintf('Product[id: %s] was not found', $lineItem->getReferencedId())
            );
        }

        $imageUrl = $this->productDataHelper->getCoverImageUrl($context->getContext(), $product);
        $viewPageUrl = $this->productDataHelper->getProductViewPageUrlByContext($product, $context);
        $categories = $this->productDataHelper->getCategoryNames($context->getContext(), $product);

        return new CheckoutLineItemInfo(
            $lineItem->getLabel(),
            $lineItem->getReferencedId(),
            $product->getProductNumber(),
            $categories,
            $imageUrl,
            $viewPageUrl,
            $lineItem->getQuantity(),
            $lineItem->getPrice()->getUnitPrice(),
            $lineItem->getPrice()->getTotalPrice(),
            $this->productDataHelper->getManufacturerName($context->getContext(), $product) ?? ''
        );
    }
}
