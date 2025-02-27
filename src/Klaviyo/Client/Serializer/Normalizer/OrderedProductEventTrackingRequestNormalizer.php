<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderedProductEvent\OrderedProductEventTrackingRequest;
class OrderedProductEventTrackingRequestNormalizer extends AbstractNormalizer
{
    /**
     * @param OrderedProductEventTrackingRequest $object
     * @param string|null $format
     * @param array $context
     * @return array[]
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $categories = '';

        if (!empty($object->getCategories())) {
            $categories = implode(',', $object->getCategories());
        }

        $properties = [
            'ProductName' => $object->getProductName(),
            'OrderId' => $object->getOrderId(),
            'ProductID' => $object->getProductId(),
            'SKU' => $object->getSku(),
            'Quantity' => $object->getQuantity(),
            'ProductURL' => $object->getProductURL(),
            'ImageURL' => $object->getImageURL(),
            'Categories' => $categories,
            'ProductBrand' => $object->getProductBrand()
        ];

        if ($object->getCustomOptions()) {
            $properties['CustomOptions'] = $object->getCustomOptions();
        }

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
                            'attributes' => [
                                'email' => $object->getCustomerProperties()->getEmail()
                            ]
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
