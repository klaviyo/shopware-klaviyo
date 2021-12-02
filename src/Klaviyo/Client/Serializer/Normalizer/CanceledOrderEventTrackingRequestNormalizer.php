<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\CanceledOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;

class CanceledOrderEventTrackingRequestNormalizer extends ConfigurableOrderEventTrackingRequestNormalizer
{
    public function __construct(ConfigurationInterface $configuration)
    {
        parent::__construct($configuration, CanceledOrderEventTrackingRequest::class, 'Cancelled Order');
    }

    /**
     * @param CanceledOrderEventTrackingRequest $object
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