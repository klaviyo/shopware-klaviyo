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
        unset($customerProperties['phone_number']);

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
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'time' => $object->getTime()->format('Y-m-d\TH:i:s'),
                    'value' => $object->getCartTotal(),
                    'unique_id' => $object->getEventId() . '_' . $object->getTime()->getTimestamp(),
                    'properties' => $properties,
                    'metric' => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => [
                                'name' => 'Added to Cart'
                            ]
                        ]
                    ],
                    'profile' => [
                        'data' => [
                            'type' => 'profile',
                            'id' => '',
                            'attributes' => $customerProperties
                        ]
                    ]
                ]
            ]
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof AddedToCartEventTrackingRequest;
    }
}
