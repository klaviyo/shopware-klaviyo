<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;


use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderedProductEvent\OrderedProductEventTrackingRequest;

class OrderedProductEventTrackingRequestNormalizer extends AbstractNormalizer
{
    /**
     * @param OrderedProductEventTrackingRequest $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $customerProperties = $this->normalizeObject($object->getCustomerProperties());
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $properties = [
            'ProductName' => $object->getProductName(),
            'OrderedProductValue' => $object->getValue(),
            '$event_id' => $object->getProductId() . '_' . $object->getOrderId() . '_' . $object->getQuantity(),
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
            'token' => $this->getToken(),
            'event' => 'Ordered Product',
            'customer_properties' => $customerProperties,
            'properties' => $properties,
            'time' => $object->getTime()->getTimestamp()
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof OrderedProductEventTrackingRequest;
    }
}
