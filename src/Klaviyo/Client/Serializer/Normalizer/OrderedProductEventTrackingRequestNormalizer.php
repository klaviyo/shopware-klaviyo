<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderedProductEvent\OrderedProductEventTrackingRequest;

class OrderedProductEventTrackingRequestNormalizer extends AbstractNormalizer
{
    /**
     * @param OrderedProductEventTrackingRequest $object
     *
     * @return array
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $customerProperties = $this->normalizeObject($object->getCustomerProperties());

        unset($customerProperties['phone_number']);

        $properties = [
            'ProductName' => $object->getProductName(),
            'OrderId' => $object->getOrderId(),
            'ProductID' => $object->getProductId(),
            'SKU' => $object->getSku(),
            'Quantity' => $object->getQuantity(),
            'ProductURL' => $object->getProductURL(),
            'ImageURL' => $object->getImageURL(),
            'Categories' => $object->getCategories(),
            'ProductBrand' => $object->getProductBrand()
        ];

        return [
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'time' => $object->getTime()->format('Y-m-d\TH:i:s'),
                    'value' => $object->getValue(),
                    'unique_id' => $object->getEventId() . '_' . $object->getTime()->getTimestamp(),
                    'properties' => $properties,
                    'metric' => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => [
                                'name' => 'Ordered Product'
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
        return $data instanceof OrderedProductEventTrackingRequest;
    }
}
