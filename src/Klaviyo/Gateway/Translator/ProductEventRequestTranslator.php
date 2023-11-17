<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderedProductEvent\OrderedProductEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Exception\OrderItemProductNotFound;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
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
        try {
            $product = $this->productDataHelper->getLineItemProduct($context, $lineItem);
            $productUrl = $this->productDataHelper->getProductViewPageUrlByChannelId(
                $product,
                $orderEntity->getSalesChannelId(),
                $context
            );
            $productNumber = $product->getProductNumber();
            $imageUrl = $this->productDataHelper->getCoverImageUrl($context, $product);
            $categories = $this->productDataHelper->getCategoryNames($context, $product);
            $manufacturerName = $this->productDataHelper->getManufacturerName($context, $product) ?? '';
        } catch (OrderItemProductNotFound $e) {
            // TODO: fix such behavior more elegant in future.
            $productUrl = $imageUrl = $manufacturerName = '';
            $productNumber = 'deleted';
            $categories = [];
        }

        $customerProperties = $this->translator->translateOrder($context, $orderEntity);

        $orderIdentificationFlag = $context->orderIdentificationFlag ?? null;

        return new OrderedProductEventTrackingRequest(
            $lineItem->getId(),
            $orderEntity->getCreatedAt(),
            $customerProperties,
            $lineItem->getUnitPrice(),
            $orderIdentificationFlag == 'order-id' ? $lineItem->getOrderId() : $orderEntity->getOrderNumber(),
            $lineItem->getProductId() ?? '',
            $productNumber,
            $lineItem->getLabel(),
            $lineItem->getQuantity(),
            $productUrl,
            $imageUrl,
            $categories,
            $manufacturerName
        );
    }
}
