<?php

namespace Klaviyo\Integration\Klaviyo\FrontendApi\Translator;

use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\CheckoutLineItemInfo;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\CheckoutLineItemInfoCollection;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\StartedCheckoutEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\Translator\CustomerPropertiesTranslator;
use Klaviyo\Integration\Storefront\Checkout\Cart\RestoreUrlService\RestoreUrlServiceInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class StartedCheckoutEventTrackingRequestTranslator
{
    public function __construct(
        private readonly RestoreUrlServiceInterface $urlGenerator,
        private readonly ProductDataHelper $productDataHelper,
        private readonly CustomerPropertiesTranslator $customerPropertiesTranslator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function translate(SalesChannelContext $context, Cart $cart): StartedCheckoutEventTrackingRequest
    {
        $checkoutUrl = $this->urlGenerator->getCurrentRestoreUrl($context);
        $lineItems = $this->translateToCheckoutLineItems($context, $cart);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        if ($context->getCustomer()) {
            $customerProperties = $this->customerPropertiesTranslator->translateCustomer(
                $context->getContext(),
                $context->getCustomer()
            );
        } else {
            throw new \Exception('No customer identification data.');
        }

        return new StartedCheckoutEventTrackingRequest(
            $cart->getToken(),
            $checkoutUrl,
            $cart->getPrice()->getTotalPrice(),
            $lineItems,
            $now,
            $customerProperties
        );
    }

    private function translateToCheckoutLineItems(
        SalesChannelContext $context,
        Cart $cart
    ): CheckoutLineItemInfoCollection {
        $collection = new CheckoutLineItemInfoCollection();

        foreach ($cart->getLineItems() as $lineItem) {
            if ('product' !== $lineItem->getType()) {
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
            throw new TranslationException(\sprintf('Product[id: %s] was not found', $lineItem->getReferencedId()));
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
            $this->productDataHelper->getManufacturerName($context->getContext(), $product) ?: ''
        );
    }
}
