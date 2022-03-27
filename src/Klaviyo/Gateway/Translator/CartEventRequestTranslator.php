<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Entity\Helper\{NewsletterSubscriberHelper, ProductDataHelper};
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\AddedToCartEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\DTO\{CartProductInfo,
    CartProductInfoCollection};
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartEventRequestTranslator
{
    private CustomerPropertiesTranslator $customerPropertiesTranslator;
    private ProductDataHelper $productDataHelper;
    private UrlGeneratorInterface $urlGenerator;
    private NewsletterSubscriberHelper $newsletterSubscriberHelper;

    public function __construct(
        CustomerPropertiesTranslator $customerPropertiesTranslator,
        ProductDataHelper $productDataHelper,
        UrlGeneratorInterface $urlGenerator,
        NewsletterSubscriberHelper $newsletterSubscriberHelper
    ) {
        $this->customerPropertiesTranslator = $customerPropertiesTranslator;
        $this->productDataHelper = $productDataHelper;
        $this->urlGenerator = $urlGenerator;
        $this->newsletterSubscriberHelper = $newsletterSubscriberHelper;
    }

    public function translateToAddedToCartEventRequest(
        SalesChannelContext $context,
        Cart $cart,
        LineItem $lineItem,
        \DateTimeInterface $time
    ): AddedToCartEventTrackingRequest {
        if (!$context->getCustomer() && isset($_COOKIE["klaviyo_subscriber"])) {
            $customer = $this->newsletterSubscriberHelper->getSubscriber($_COOKIE["klaviyo_subscriber"],
                $context->getContext());
            $customerProperties = $this->customerPropertiesTranslator
                ->translateCustomer($context->getContext(), $customer);
        } else {
            $customerProperties = $this->customerPropertiesTranslator
                ->translateCustomer($context->getContext(), $context->getCustomer());
        }

        $addedProductInfo = $this->translateToCartProductInfo($context, $lineItem);
        $checkoutUrl = $this->urlGenerator
            ->generate(
                'frontend.checkout.confirm.page',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

        $collection = new CartProductInfoCollection();
        foreach ($cart->getLineItems() as $cartLineItem) {
            if ($cartLineItem->getType() !== 'product') {
                continue;
            }

            $collection->add($this->translateToCartProductInfo($context, $cartLineItem));
        }

        return new AddedToCartEventTrackingRequest(
            $lineItem->getId(),
            $time,
            $customerProperties,
            $cart->getPrice()->getTotalPrice(),
            $lineItem->getPrice()->getTotalPrice(),
            $lineItem->getLabel(),
            $lineItem->getReferencedId(),
            $addedProductInfo->getSku(),
            $addedProductInfo->getProductCategories(),
            $addedProductInfo->getImageUrl(),
            $addedProductInfo->getViewPageUrl(),
            $lineItem->getQuantity(),
            $checkoutUrl,
            $collection
        );
    }

    private function translateToCartProductInfo(SalesChannelContext $context, LineItem $lineItem): CartProductInfo
    {
        $product = $this->productDataHelper->getProductById($context->getContext(), $lineItem->getReferencedId());
        if (!$product) {
            throw new TranslationException(
                sprintf('Product[id: %s] was not found', $lineItem->getReferencedId())
            );
        }

        $imageUrl = $this->productDataHelper->getProductViewPageUrl($product);
        $viewPageUrl = $this->productDataHelper->getProductViewPageUrl($product);
        $categories = $this->productDataHelper->getCategoryNames($context->getContext(), $product);

        $price = $lineItem->getPrice();

        return new CartProductInfo(
            $lineItem->getReferencedId(),
            $product->getProductNumber(),
            $lineItem->getLabel(),
            $lineItem->getQuantity(),
            $price ? $price->getUnitPrice() : 0.0,
            $price ? $lineItem->getPrice()->getTotalPrice() : 0.0,
            $imageUrl,
            $viewPageUrl,
            $categories
        );
    }
}