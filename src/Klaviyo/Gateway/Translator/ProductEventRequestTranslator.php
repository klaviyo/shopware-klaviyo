<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderedProductEvent\OrderedProductEventTrackingRequest;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;

class ProductEventRequestTranslator
{
    private CustomerPropertiesTranslator $translator;
    private ProductDataHelper $productDataHelper;

    public function __construct(
        CustomerPropertiesTranslator $translator,
        ProductDataHelper $productDataHelper
    ) {
        $this->translator = $translator;
        $this->productDataHelper = $productDataHelper;
    }

    public function translateToOrderedProductEventRequest(
        Context $context,
        OrderLineItemEntity $lineItem,
        OrderEntity $orderEntity
    ): OrderedProductEventTrackingRequest {
        $product = $this->getLineItemProduct($lineItem, $context);

        $customerProperties = $this->translator->translateOrder($context, $orderEntity);

        $productUrl = $this->productDataHelper->getProductViewPageUrl(
            $product,
            null,
            $context,
            $orderEntity->getSalesChannelId()
        );
        $imageUrl = $this->productDataHelper->getCoverImageUrl($context, $product);
        $categories = $this->productDataHelper->getCategoryNames($context, $product);
        $manufacturerName = $this->productDataHelper->getManufacturerName($context, $product) ?? '';

        return new OrderedProductEventTrackingRequest(
            $lineItem->getId(),
            $orderEntity->getCreatedAt(),
            $customerProperties,
            $lineItem->getUnitPrice(),
            $lineItem->getOrderId(),
            $lineItem->getProductId(),
            $product->getProductNumber(),
            $lineItem->getLabel(),
            $lineItem->getQuantity(),
            $productUrl,
            $imageUrl,
            $categories,
            $manufacturerName
        );
    }

    private function getLineItemProduct(OrderLineItemEntity $lineItemEntity, Context $context): ProductEntity
    {
        if ($lineItemEntity->getProduct()) {
            return $lineItemEntity->getProduct();
        }

        return $this->productDataHelper->getProductById($context, $lineItemEntity->getProductId());
    }
}