<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\PaidOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException;

class PaidOrderEventTrackingRequestNormalizer extends ConfigurableOrderEventTrackingRequestNormalizer
{
    public function __construct(ConfigurationInterface $configuration)
    {
        parent::__construct($configuration, PaidOrderEventTrackingRequest::class, 'Paid Order');
    }

    /**
     * @param PaidOrderEventTrackingRequest $object
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
