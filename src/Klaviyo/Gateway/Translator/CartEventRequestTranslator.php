<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Storefront\Checkout\Cart\RestoreUrlService\RestoreUrlServiceInterface;
use Klaviyo\Integration\Entity\Helper\{NewsletterSubscriberHelper, ProductDataHelper};
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\AddedToCartEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\DTO as CartEventDTO;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

class CartEventRequestTranslator
{
    private CustomerPropertiesTranslator $customerPropertiesTranslator;
    private ProductDataHelper $productDataHelper;
    private RestoreUrlServiceInterface $urlGenerator;
    private NewsletterSubscriberHelper $newsletterSubscriberHelper;
    private RequestStack $requestStack;
    private NewsletterSubscriberPropertiesTranslator $newsletterSubscriberPropertiesTranslator;

    public function __construct(
        CustomerPropertiesTranslator $customerPropertiesTranslator,
        ProductDataHelper $productDataHelper,
        RestoreUrlServiceInterface $urlGenerator,
        NewsletterSubscriberHelper $newsletterSubscriberHelper,
        RequestStack $requestStack,
        NewsletterSubscriberPropertiesTranslator $newsletterSubscriberPropertiesTranslator
    ) {
        $this->customerPropertiesTranslator = $customerPropertiesTranslator;
        $this->productDataHelper = $productDataHelper;
        $this->urlGenerator = $urlGenerator;
        $this->newsletterSubscriberHelper = $newsletterSubscriberHelper;
        $this->requestStack = $requestStack;
        $this->newsletterSubscriberPropertiesTranslator = $newsletterSubscriberPropertiesTranslator;
    }

    /**
     * @throws \Exception
     */
    public function translateToAddedToCartEventRequest(
        SalesChannelContext $context,
        Cart $cart,
        LineItem $lineItem,
        \DateTimeInterface $time
    ): AddedToCartEventTrackingRequest {
        $request = $this->requestStack->getCurrentRequest();
        $subscriberId = (string)$request->cookies->get('klaviyo_subscriber');

        if ($context->getCustomer()) {
            $customerProperties = $this->customerPropertiesTranslator->translateCustomer(
                $context->getContext(),
                $context->getCustomer()
            );
        } else {
            $subscriber = $this->newsletterSubscriberHelper->getSubscriber($subscriberId, $context->getContext());

            if ($subscriber) {
                $customerProperties = $this->newsletterSubscriberPropertiesTranslator->translateSubscriber($subscriber);
            } else {
                throw new \Exception('No customer identification data.');
            }
        }

        $addedProductInfo = $this->translateToCartProductInfo($context, $lineItem);
        $checkoutUrl = $this->urlGenerator
            ->getCurrentRestoreUrl($context);

        $collection = new CartEventDTO\CartProductInfoCollection();
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

    private function translateToCartProductInfo(
        SalesChannelContext $context,
        LineItem $lineItem
    ): CartEventDTO\CartProductInfo {
        $product = $this->productDataHelper->getProductById($context->getContext(), $lineItem->getReferencedId());
        if (!$product) {
            throw new TranslationException(
                \sprintf('Product[id: %s] was not found', $lineItem->getReferencedId())
            );
        }

        $imageUrl = $this->productDataHelper->getCoverImageUrl($context->getContext(), $product);
        $viewPageUrl = $this->productDataHelper->getProductViewPageUrlByContext($product, $context);
        $categories = $this->productDataHelper->getCategoryNames($context->getContext(), $product);
        $price = $lineItem->getPrice();

        return new CartEventDTO\CartProductInfo(
            $lineItem->getReferencedId(),
            $product->getProductNumber(),
            $lineItem->getLabel(),
            $lineItem->getQuantity(),
            $price ? $price->getUnitPrice() : 0.0,
            $price ? $lineItem->getPrice()->getTotalPrice() : 0.0,
            $imageUrl,
            $viewPageUrl,
            $categories,
            $this->productDataHelper->getManufacturerName($context->getContext(), $product) ?: ''
        );
    }
}
