<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\ShippedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException;

class ShippedOrderEventTrackingRequestNormalizer extends ConfigurableOrderEventTrackingRequestNormalizer
{
    public function __construct(ConfigurationInterface $configuration)
    {
        parent::__construct($configuration, ShippedOrderEventTrackingRequest::class, 'Shipped Order');
    }

    /**
     * @param ShippedOrderEventTrackingRequest $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     * @throws SerializationException
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $normalizedData = parent::normalize($object);

        $normalizedData['properties']['Reason'] = $object->getReason();

        return $normalizedData;
    }
}
