<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Entity\Helper\{NewsletterSubscriberHelper, ProductDataHelper};
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\AddedToCartEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\DTO\{
    CartProductInfo,
    CartProductInfoCollection
};
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartEventRequestTranslator
{
    private CustomerPropertiesTranslator $customerPropertiesTranslator;
    private ProductDataHelper $productDataHelper;
    private UrlGeneratorInterface $urlGenerator;
    private NewsletterSubscriberHelper $newsletterSubscriberHelper;
    private RequestStack $requestStack;
    private NewsletterSubscriberPropertiesTranslator $newsletterSubscriberPropertiesTranslator;

    public function __construct(
        CustomerPropertiesTranslator $customerPropertiesTranslator,
        ProductDataHelper $productDataHelper,
        UrlGeneratorInterface $urlGenerator,
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

    public function translateToAddedToCartEventRequest(
        SalesChannelContext $context,
        Cart $cart,
        LineItem $lineItem,
        \DateTimeInterface $time
    ): AddedToCartEventTrackingRequest {
        $request = $this->requestStack->getCurrentRequest();
        $klaviyoNewsletterSubscriberId = $request->cookies->get('klaviyo_subscriber') ?? null;
        if (!$context->getCustomer() && $klaviyoNewsletterSubscriberId) {
            $customer = $this->newsletterSubscriberHelper->getSubscriber(
                $klaviyoNewsletterSubscriberId,
                $context->getContext()
            );
            $customerProperties = $this->newsletterSubscriberPropertiesTranslator
                ->translateSubscriber($customer);
        } elseif ($context->getCustomer()) {
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