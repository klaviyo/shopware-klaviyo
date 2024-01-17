<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\AddedToCartEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\DTO\CartProductInfo;

class AddedToCartEventTrackingRequestNormalizer extends AbstractNormalizer
{
    /**
     * @param AddedToCartEventTrackingRequest $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $customerProperties = $this->normalizeObject($object->getCustomerProperties());

        $itemNames = [];
        $productItems = [];

        /** @var CartProductInfo $productInfo */
        foreach ($object->getCartProductInfoCollection() as $productInfo) {
            $itemNames[] = $productInfo->getName();
            $productItems[] = [
                'ProductID' => $productInfo->getId(),
                'SKU' => $productInfo->getSku(),
                'ProductName' => $productInfo->getName(),
                'Quantity' => $productInfo->getQuantity(),
                'ItemPrice' => $productInfo->getPrice(),
                'RowTotal' => $productInfo->getRowTotal(),
                'ProductURL' => $productInfo->getViewPageUrl(),
                'ImageURL' => $productInfo->getImageUrl(),
                'ProductCategories' => $productInfo->getProductCategories(),
                'Brand' => $productInfo->getBrand()
            ];
        }

        $properties = [
            '$value' => $object->getCartTotal(),
            'ProductName' => $object->getAddedItemProductName(),
            'ProductID' => $object->getAddedItemProductId(),
            'SKU' => $object->getAddedItemProductSKU(),
            'Categories' => $object->getAddedItemCategoryNames(),
            'ImageURL' => $object->getAddedItemImageUrl(),
            'URL' => $object->getAddedItemUrl(),
            'Price' => $object->getAddedItemPrice(),
            'Quantity' => $object->getAddedItemQty(),
            'ItemNames' => $itemNames,
            'CheckoutURL' => $object->getCheckoutURL(),
            'Items' => $productItems
        ];

        return [
            'token' => $this->getToken(),
            'event' => 'Added to Cart',
            'customer_properties' => $customerProperties,
            'properties' => $properties,
            'time' => $object->getTime()->getTimestamp()
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof AddedToCartEventTrackingRequest;
    }
}
