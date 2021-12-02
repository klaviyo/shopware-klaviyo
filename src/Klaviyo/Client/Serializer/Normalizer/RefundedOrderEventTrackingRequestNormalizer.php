<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\RefundedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;

class RefundedOrderEventTrackingRequestNormalizer extends ConfigurableOrderEventTrackingRequestNormalizer
{
    public function __construct(ConfigurationInterface $configuration)
    {
        parent::__construct($configuration, RefundedOrderEventTrackingRequest::class, 'Refunded Order');
    }

    /**
     * @param RefundedOrderEventTrackingRequest $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $normalizedData = parent::normalize($object);

        $normalizedData['properties']['Reason'] = $object->getReason();

        return $normalizedData;
    }
}